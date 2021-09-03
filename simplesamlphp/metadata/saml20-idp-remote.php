<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

$metadata['https://login.max.gov/idp/shibboleth'] = [
  'entityid' => 'https://login.max.gov/idp/shibboleth',
  'contacts' => [],
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => [
      0 => [
          'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
          'Location' => 'https://login.max.gov/idp/profile/Shibboleth/SSO',
        ],
      1 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://login.max.gov/idp/profile/SAML2/POST/SSO',
        ],
      2 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
          'Location' => 'https://login.max.gov/idp/profile/SAML2/POST-SimpleSign/SSO',
        ],
      3 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://login.max.gov/idp/profile/SAML2/Redirect/SSO',
        ],
    ],
  'SingleLogoutService' => [],
  'ArtifactResolutionService' => [],
  'NameIDFormats' => [],
  'keys' => [
      0 => [
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => '
MIIDHzCCAgegAwIBAgIUPdRdrl5geFw6rcdLCg9XQr5fpqowDQYJKoZIhvcNAQEF BQAwGDEWMBQGA1UEAwwNbG9naW4ubWF4LmdvdjAeFw0xNTAyMjcxNzM0MjVaFw0z NTAyMjcxNzM0MjVaMBgxFjAUBgNVBAMMDWxvZ2luLm1heC5nb3YwggEiMA0GCSqG SIb3DQEBAQUAA4IBDwAwggEKAoIBAQCP/XwjR/J27ORJWOdK+Kfj3UE74x2OrrVp RvBGRkzv34YY7bSApD0s/WOz2h4fHa496LSZ8mc2ZmY6Tcmq2U1Sy+W6wECPr/Bj ZXpJPzAh3BBnrnO41lD8RIHBmpvPxPsOdrGwxOwVggg86fN31RI0gBHcbn3KPz7s K/9cHC55QL01qzpjhCCp1cZ2ZrEzfu3V1jpRoIsOYWIXlbj2Fn+rziOUrnUO+eMF pwDeifJqKUXBV7ZM8VejC9Z60uNmV2JPm9CHnjhCxul0fAChm+vPsw1DneoAw1m1 LZk/SmuKqFVHuLVBn32I/lUuK/ugr8ww1FPMaqtdR46s5bTe+tYTAgMBAAGjYTBf MB0GA1UdDgQWBBRky4lFS031okDAefZKehA27/DZIDA+BgNVHREENzA1gg1sb2dp bi5tYXguZ292hiRodHRwczovL2xvZ2luLm1heC5nb3YvaWRwL3NoaWJib2xldGgw DQYJKoZIhvcNAQEFBQADggEBAD/dpBgAQMwbHakIDukwDOX2GBWu+l+jZt/1KqlZ YuxeNjRB54rZp70SOkARlUtWP8fdm6Lp1R1JxzqIsI8nde0lBCXw21lGQDzXVm+z rMmsS/KS9N1WM9Wqg0VJgTC4EHnK1OxfUVfH6gG6GV8+pSTv2tM2SKBiG5cQ9g/i 2mh/M8aPg05TA+IZCMOnKIgnkEq3YhI2OS80a9qrSKZh8X4/+DklGHWzbdOV8pW0 CQ3LQo/QLeCJHTdqga2i5y0aKcyX3d7pNlJZh1PMInz9Lmd4WFHllaDgRxWsWCRW x1DFvVHKK/lPRTV+5Emt3dzy+gVd1ZnSxCVbkt2SswlPdGI=
',
        ],
    ],
  'scope' => [
      0 => 'max.gov',
    ],
];

$metadata['https://login.test.max.gov/idp/shibboleth'] = [
  'entityid' => 'https://login.test.max.gov/idp/shibboleth',
  'contacts' => [],
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => [
      0 => [
          'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
          'Location' => 'https://login.test.max.gov/idp/profile/Shibboleth/SSO',
        ],
      1 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://login.test.max.gov/idp/profile/SAML2/POST/SSO',
        ],
      2 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
          'Location' => 'https://login.test.max.gov/idp/profile/SAML2/POST-SimpleSign/SSO',
        ],
      3 => [
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://login.test.max.gov/idp/profile/SAML2/Redirect/SSO',
        ],
    ],
  'SingleLogoutService' => [],
  'ArtifactResolutionService' => [],
  'NameIDFormats' => [],
  'keys' => [
      0 => [
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => '
MIIDMzCCAhugAwIBAgIUGZmNOfGrnHuo8FkedfSoNuXGh0swDQYJKoZIhvcNAQEF BQAwHTEbMBkGA1UEAwwSbG9naW4udGVzdC5tYXguZ292MB4XDTE1MDIyNjE4NTgy MloXDTM1MDIyNjE4NTgyMlowHTEbMBkGA1UEAwwSbG9naW4udGVzdC5tYXguZ292 MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAl6OO43kdFgZFYNBxBxnW f965G3h0Z1l+CM4rfDoRW7ieIiYnkolsln738hb21M8Q0SXqniKFGaptUNNyTGkB 5R8Dk1zljkrh4KdnKhj3gZu2OnjJ8L4ihR0gdiJuXxvVGaI+KcU0b2Ahz4TBi/DZ ts4c4CJFzmdFL57QjOsBT8jgg3tXQDncl+w0kx+fGFaVTS6tIsN18LscFr0lmHEE E0w3vfOu5CP2G3+MPnJ2ij6urmJdsxyRqHdiHKS3ItpCTWMmt5duvlg3QPK/21C9 J7nnuDXPSfhym0gihXvdNt71y4aDI3tqXR3eIaz7ljjEO2PDG6yJwMsE23HhEbW9 FwIDAQABo2swaTAdBgNVHQ4EFgQUDBhTOWKufUoHOvgmiZO0gFohONIwSAYDVR0R BEEwP4ISbG9naW4udGVzdC5tYXguZ292hilodHRwczovL2xvZ2luLnRlc3QubWF4 Lmdvdi9pZHAvc2hpYmJvbGV0aDANBgkqhkiG9w0BAQUFAAOCAQEABmVizMnSUZ0g AB13t0KdmVqdDh3fp0wsuj9XhUyWlaOyWt8FtcKrr4V3eH281Of1VaG4IAgmHynr CyyDlaU+2rN3X9Mnaz2kgt7fYMiVbU945h4h8X8+DqS4fl+HEP0OpSG0rqTAJ1yN A0nmnYZEeKDwJbTUXaL7w5D+4WNNYDpJ+yVEAno98cLPZtgh0NlpdEl09SK/k0Bm aY6ptcDxOa7FfTeQX9GUmulJTErLen/QHoQf6mQN14y1woXwI/kPpAD8C4Wi5N/P Z00nZfcqMpeatQMt91IiI2IRSInyZ8UU0UqdY3XIJFDDoXyK/SsI5NBZksz0MrbG lJsgPWOAxg==
',
        ],
    ],
  'scope' => [
      0 => 'max.gov',
    ],
];

$metadata['https://login.stage.max.gov/idp/shibboleth'] = array (
  'entityid' => 'https://login.stage.max.gov/idp/shibboleth',
  'contacts' =>
  array (
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://login.stage.max.gov/idp/profile/Shibboleth/SSO',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/POST/SSO',
    ),
    2 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    3 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/Redirect/SSO',
    ),
  ),
  'SingleLogoutService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/Redirect/SLO',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/POST/SLO',
    ),
    2 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/SOAP/SLO',
    ),
  ),
  'ArtifactResolutionService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML1/SOAP/ArtifactResolution',
      'index' => 1,
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://login.stage.max.gov/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 2,
    ),
  ),
  'NameIDFormats' =>
  array (
    0 => 'urn:mace:shibboleth:1.0:nameIdentifier',
    1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  ),
  'keys' =>
  array (
    0 =>
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIDODCCAiCgAwIBAgIVAIt37bpkVQbi5DGlmw+bIzL8qIDdMA0GCSqGSIb3DQEBBQUAMB4xHDAaBgNVBAMME2xvZ2luLnN0YWdlLm1heC5nb3YwHhcNMTYwNjIzMTcxMTEwWhcNMzYwNjIzMTcxMTEwWjAeMRwwGgYDVQQDDBNsb2dpbi5zdGFnZS5tYXguZ292MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmDOvx90OJIEihGG87XMF5kPIoM5+4o9/8bLsr1B+11m6swyD69fweFxSC898sQp64ecdkWqX85CRXn04zh0IyINPgCbZA8JhvdCCeY6ZsWMvVj8WDM3o8BDT+owAcTWeekJQwtC4AsUEgYUjfsZ3SaNLfRVrog1ZoLZqARLVMMDBmkV2BO1qcEQZMlAauPPnKLuCG053eeFozqU3Jwp62whU14fN/YGp8g9V6jJFl3Ryw5DfF1sjuu0ZV48UxvpojGq+tYCmOTDT3x47kP+auiD8ZPs1GeUCpXoR9PDcvXzdFk04XXBiiYEBYvL6jUwPhgVdF1pquy6uaM3VioVRYwIDAQABo20wazAdBgNVHQ4EFgQUjZgk45a/61ud3ajgA29Wmf2Jf48wSgYDVR0RBEMwQYITbG9naW4uc3RhZ2UubWF4LmdvdoYqaHR0cHM6Ly9sb2dpbi5zdGFnZS5tYXguZ292L2lkcC9zaGliYm9sZXRoMA0GCSqGSIb3DQEBBQUAA4IBAQCPJZ939ZWE1EwyTlt1p+nkLQGsQ1IWaBPsBR5cE01NCcndj0B3yUMo3ZpaENLYvSh7hglgh7HH8NdEo01eSwkTsUYnzL2GAjiIGbgXcWyqoVugCw/qIkL/32R1qsw+/yhcbHwrstApH5B8sS5EMI/Ky9nMG1WfzJOploXCEmBryodM8S3CHgnBmd7Fq9eEq3cNbAZG/BZlO92B8frFFQWNvNtua88dMRtyzRbrwT66vi+7FqTEx0k5gMWXf+U18qQuZ1KHxlEXUIWssobl5E+12XYwjgXhFGX9iLxvbRBIhb0+6OcwEyXKbXnLKDR1jRJ6bOBImKg09FKwk3eR0CPH',
    ),
  ),
  'scope' =>
  array (
    0 => 'max.gov',
  ),
);
