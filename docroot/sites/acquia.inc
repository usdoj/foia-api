<?php

use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * Utilities for use in protecting an environment via basic auth or IP whitelist.
 */
function ac_protect_this_site() {
  global $config;

  // Create the Request object and whitelist Acquia load balancers.
  $request = Request::createFromGlobals();
  if (!empty($config['ah_trusted_proxies'])) {
    Request::setTrustedProxies($config['ah_trusted_proxies'], Request::HEADER_X_FORWARDED_ALL);
  }
  else {
    Request::setTrustedProxies(array($request->server->get('REMOTE_ADDR')), Request::HEADER_X_FORWARDED_ALL);
  }

  $denied_response = !empty($config['ah_denied_not_found']) ? 404 : 403;
  $client_ip = $request->getClientIp();
  $current_path = $request->getRequestUri();

  // Test if we are using drush (command-line interface)
  $cli = PHP_SAPI === 'cli';

  // Default to not skipping the auth check
  $skip_auth_check = FALSE;

  // Is the user on the VPN? Default to FALSE.
  $on_vpn = $cli ? TRUE : FALSE;

  if (!empty($client_ip) && !empty($config['ah_whitelist'])) {
    $on_vpn = ah_ip_in_list($client_ip, $config['ah_whitelist']);
    $skip_auth_check = $skip_auth_check || $on_vpn;
  }

  // If the IP is not explicitly whitelisted check to see if the IP is blacklisted.
  if (!$on_vpn && !empty($client_ip) && !empty($config['ah_blacklist'])) {
    if (ah_ip_in_list($client_ip, $config['ah_blacklist'])) {
      ah_response($denied_response, $client_ip, $current_path);
    }
  }

  // Check if we should skip auth check for this page.
  if (ah_path_skip_auth($current_path)) {
    $skip_auth_check = TRUE;
  }

  // Check if we should disable cache for this page.
  if (ah_path_no_cache($current_path)) {
    $config['system.performance']['cache']['page']['max_age'] = 0;
  }

  // Is the page restricted to whitelist only? Default to FALSE.
  $restricted_page = FALSE;

  // Check to see whether this page is restricted.
  if (!empty($config['ah_restricted_paths']) && ah_paths_restrict($current_path)) {
    $restricted_page = TRUE;
  }

  $protect_ip = !empty($config['ah_whitelist']);
  $protect_password = !empty($config['ah_basic_auth_credentials']);

  // Do not protect command line requests, e.g. Drush.
  if ($cli) {
    $protect_ip = FALSE;
    $protect_password = FALSE;
  }

  // Un-comment to disable protection, e.g. for load tests.
  // $skip_auth_check = TRUE;
  // $on_vpn = TRUE;

  // If not on whitelisted IP prevent access to protected pages.
  if ($protect_ip && !$on_vpn && $restricted_page) {
    ah_response($denied_response, $client_ip, $current_path);
  }

  // If not skipping auth, check basic auth.
  if ($protect_password && !$skip_auth_check) {
    ah_check_basic_auth($client_ip);
  }
}

/**
 * Deny access to the page with a customisable response.
 */
function ah_response($response, $client_ip, $current_path) {
  switch ($response) {
    case 401:
      header('WWW-Authenticate: Basic realm="This site is protected"');
      header('HTTP/1.0 401 Unauthorized');
      print "401 Unauthorized: Access denied ($client_ip)";
      exit;
      break;
    case 403:
      header('HTTP/1.0 403 Forbidden');
      header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
      print "403 Forbidden: Access denied (${client_ip})";
      exit;
      break;
    case 404:
      header('HTTP/1.0 404 Not Found');
      print "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ${current_path} was not found on this server.</p></body></html>";
      exit;
      break;
  }
}

/**
 * Check basic auth against allowed values.
 */
function ah_check_basic_auth($client_ip) {
  global $config;

  $authorized = FALSE;
  $php_auth_user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
  $php_auth_pw = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
  $credentials = isset($config['ah_basic_auth_credentials']) ? $config['ah_basic_auth_credentials'] : NULL;

  if ($php_auth_user && $php_auth_pw && !empty($credentials)) {
    if (isset($credentials[$php_auth_user]) && $credentials[$php_auth_user] == $php_auth_pw) {
      $authorized = TRUE;
    }
  }

  if ($authorized) {
    return;
  }

  // Always fall back to 401.
  ah_response(401, $client_ip);
}

/**
 * Determine if the current path is in the list of paths to not cache.
 */
function ah_path_no_cache($current_path) {
  global $config;

  $paths = isset($config['ah_paths_no_cache']) ? $config['ah_paths_no_cache'] : NULL;
  if (isset($current_path) && !empty($paths)) {
    foreach ($paths as $path) {
      if ($current_path == $path || strpos($current_path, $path) === 0) {
        return TRUE;
      }
    }
  }
}

/**
 * Determine if the current path is in the list of paths on which to not check
 * auth.
 */
function ah_path_skip_auth($current_path) {
  global $config;

  $paths = isset($config['ah_paths_skip_auth']) ? $config['ah_paths_skip_auth'] : NULL;
  if (isset($current_path) && !empty($paths)) {
    foreach ($paths as $path) {
      if ($current_path == $path || strpos($current_path, $path) === 0) {
        return TRUE;
      }
    }
  }
}

/**
 * Check whether a path has been restricted.
 *
 */
function ah_paths_restrict($current_path) {
  global $config;

  // Borrow some code from drupal_match_path()
  foreach ($config['ah_restricted_paths'] as &$path) {
    $path = preg_quote($path, '/');
  }

  $paths = preg_replace('/\\\\\*/', '.*', $config['ah_restricted_paths']);
  $paths = '/^(' . join('|', $paths) . ')$/';

  // If this is a restricted path, return TRUE.
  if (preg_match($paths, $current_path)) {
    // Do not cache restricted paths
    $config['system.performance']['cache']['page']['max_age'] = 0;
    return TRUE;
  }

  return FALSE;
}

/**
 * Determine if the IP is within the ranges defined in the white/black list.
 */
function ah_ip_in_list($ip, $list) {
  foreach ($list as $item) {

    // Match IPs in CIDR format.
    if (strpos($item, '/') !== false) {
      list($range, $mask) = explode('/', $item);

      // Take the binary form of the IP and range.
      $ip_dec = ip2long($ip);
      $range_dec = ip2long($range);

      // Verify the given IPs are valid IPv4 addresses
      if (!$ip_dec || !$range_dec) {
        continue;
      }

      // Create the binary form of netmask.
      $mask_dec = ~ (pow(2, (32 - $mask)) - 1);

      // Run a bitwise AND to determine whether the IP and range exist
      // within the same netmask.
      if (($mask_dec & $ip_dec) == ($mask_dec & $range_dec)) {
        return TRUE;
      }
    }

    // Match against wildcard IPs or IP ranges.
    elseif (strpos($item, '*') !== false || strpos($item, '-') !== false) {

      // Construct a range from wildcard IPs
      if (strpos($item, '*') !== false) {
        $item = str_replace('*', 0, $item) . '-' . str_replace('*', 255, $item);
      }

      // Match against ranges by converting to long IPs.
      list($start, $end) = explode('-', $item);

      $start_dec = ip2long($start);
      $end_dec = ip2long($end);
      $ip_dec = ip2long($ip);

      // Verify the given IPs are valid IPv4 addresses
      if (!$start_dec || !$end_dec || !$ip_dec) {
        continue;
      }

      if ($start_dec <= $ip_dec && $ip_dec <= $end_dec) {
        return TRUE;
      }
    }

    // Match against single IPs
    elseif ($ip === $item) {
      return TRUE;
    }
  }
  return FALSE;
}
