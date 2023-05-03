# Behat testing

Note that at the time of writing, the built-in configuration assumes the site
is running in a local ddev environment.


## Usage

Behat tests can be run one at a time by using the tags option.

```

# run all tests
ddev exec vendor/bin/behat --config=tests/behat/local.yml

# run only one test using a tag
ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @cfo

# run agencycomp
ddev exec vendor/bin/behat --config=tests/behat/behat.yml --tags @agencycomp

```


### Local Config

A local config file can be used to change file paths and the base url if there is a need.

The [example.local.yml](example.local.yml) can be used to create a `local.yml` file.



```bash

# use local config
ddev exec  vendor/bin/behat --config=tests/behat/local.yml --tags @reportdata

```
