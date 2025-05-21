# DOJ FOIA Project

The DOJ FOIA project's larger purpose is to create a single, unified portal for the submission of Freedom of Information Act (FOIA) requests.  This code base's part in that larger goal is to create a Drupal backend which will be accessed by a front-end being developed by [18F](https://18f.gsa.gov).

The backend is currently implemented on the [Lightning distro](https://github.com/acquia/lightning), stealing numerous approaches/configurations from the [Reservoir project](https://github.com/acquia/reservoir)

## BLT

Please see the [BLT documentation](http://blt.readthedocs.io/en/latest/) for information on build, testing, and deployment processes.

### Local setup

DDev and Docker can be used to spin up this project.
- Install "Docker" and run the "Docker Desktop".
- From the repo root, run the command "ddev start".
- Import the database through drush.

## Resources

* [Issue queue](https://github.com/usdoj/foia-api/issues)
* [GitHub](https://github.com/usdoj/foia-api)
