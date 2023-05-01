# Behat testing

## Usage

Behat tests can be run one at a time by using the tags option.

```

# run all tests
ddev exec vendor/bin/behat --config=tests/behat/behat.yml

# run only CFO tests
ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @cfo

# run agencycomp
ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @agencycomp


ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @administrator



```

Note that at the time of writing, the built-in configuration assumes the site
is running in a local ddev environment.
