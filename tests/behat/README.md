# Behat testing

## Usage

Behat needs to be run in the ddev bash environment, and will run every test by default.

You can run only one feature or specific scenario by using the ```--tags``` flag.

```bash
ddev exec vendor/bin/behat --config=tests/behat/behat.yml

ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @cfocommittee

# use local config
ddev exec vendor/bin/behat --config=tests/behat/local.yml --tags @cfocommittee
```

In environments with custom ddev configurations, such as Apple computers, you will need a local.yml file with custom options.

The file `example.local.yml` can be used as a template to create a `local.yml` file which will override the configuration in `behat.yml`.

You only need to place values in ```local.yml``` that you want to override in the main config file.


The built-in configuration assumes the site is running in a local ddev environment.

*As of now behat will not work in Acquia's cloud environment.*


### Cleanup

Some tests may create nodes or taxonomy terms and may need cleanup in your local behat environment.

This is not an issue in the github environment since each test is run in a new environment.


The `cleanTaxonomyTerms` will cleanup any taxonomy terms created but not purged after a failed test.

The `tests/behat/features/bootstrap/Drupal/FeatureContext.php` may need more cleanup functions in the future if new tests are added.

### Drupal 10 Issues

Several modules depend on symfony/filesystem versuin 6, but bex/
consolidation/robo 4.0.6 requires symfony/filesystem ^6 ->



### Abandoned Packages


acquia/blt-phpcs    v1.0.0 v1.0.0 PHP_CodeSniffer integration for Acquia BLT
behat/mink-goutte-driver
geerlingguy/drupal-vm
Package acquia/blt-phpcs is abandoned, you should avoid using it. No replacement was suggested.
Package webmozart/path-util is abandoned, you should avoid using it. Use symfony/filesystem instead.
Package behat/mink-goutte-driver is abandoned, you should avoid using it. Use behat/mink-browserkit-driver instead.
Package fabpot/goutte is abandoned, you should avoid using it. Use symfony/browser-kit instead.

Package acquia/blt-phpcs is abandoned, you should avoid using it. No replacement was suggested.



behat/mink-goutte-driver                       v2.0.0 v2.0.0 Goutte driver for Mink framework
Package behat/mink-goutte-driver is abandoned, you should avoid using it. Use behat/mink-browserkit-driver instead.
drupal/core_context                            1.0.0  1.1.0  Allows context values to be attached to entities in a field.
geerlingguy/drupal-vm                          6.0.4  6.0.4  A VM for local Drupal development, built with Vagrant + Ansible
Package geerlingguy/drupal-vm is abandoned, you should avoid using it. No replacement was suggested.



## testing issue

### problem 1
Expected response code 200, got 500.
(Imbo\BehatApiExtension\Exception\AssertionFailedException


### problem 2
Entity queries must explicitly set whether the query should be access checked or not.
See Drupal\Core\Entity\Query\QueryInterface::accessCheck().
(Drupal\Core\Entity\Query\QueryException)
│
└─ @AfterScenario @agency # D
