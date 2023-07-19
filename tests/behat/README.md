# Behat testing

## Usage

Behat can run all tests if you don't use any tags, but can be limited to running one feature or any one scenario by using the ```--tags``` flag.

```
vendor/bin/behat --config=tests/behat/behat.yml

vendor/bin/behat --config=tests/behat/behat.yml --tags @cfocommittee

# use local config
vendor/bin/behat --config=tests/behat/local.yml --tags @cfocommittee
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

