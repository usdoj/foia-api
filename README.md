# DOJ FOIA Project

The DOJ FOIA project's larger purpose is to create a single, unified portal for the submission of Freedom of Information Act (FOIA) requests.  This code base's part in that larger goal is to create a Drupal backend which will be accessed by a front-end being developed by [18F](https://18f.gsa.gov).

The backend is currently implemented on the [Lightning distro](https://github.com/acquia/lightning), stealing numerous approaches/configurations from the [Reservoir project](https://github.com/acquia/reservoir)

## BLT

Please see the [BLT documentation](http://blt.readthedocs.io/en/latest/) for information on build, testing, and deployment processes.

### Local setup

Follow steps 1-6 from the [initial setup section](https://blt.readthedocs.io/en/8.x/readme/onboarding/#initial-setup) in BLT's docs, as well as 1-3 from the VM steps. Note that you do not need to follow the steps to install npm/nvm on your host machine. If we end up working frontend tasks, these steps can take place within the VM.
VM-related software version suggestions:
Virtualbox - 5.1.24
Vagrant - 1.9.7
Ansible - 2.2.3

### Important Build Process note
Because we are sharing a Cloud subscription with the broader DOJ project, we need to be careful not to clobber their build artifacts in the Acquia git repo.  As a result, **when running `blt deploy` make sure to build to the foia-develop-build branch** rather than the default develop-build.

## Resources

* [Issue queue](https://acquiaps.atlassian.net/secure/RapidBoard.jspa?rapidView=522)
* [GitHub](https://github.com/usdoj/foia)
* [Acquia Cloud subscription](https://cloud.acquia.com/app/develop/applications/81f1c2d0-ea14-fa24-156f-dde8a93922ae/environments/32932-81f1c2d0-ea14-fa24-156f-dde8a93922ae) - at present, we don't have our own Cloud subscription. We just have an environment on the larger DOJ subscription. 
* TravisCI - Continuous Integration TBD

## Acquia Team

* Joshua Smith - Account Manager
* Kristus Ratliff - Engagement Manager
* Barrett Smith
* Malik Kotob
