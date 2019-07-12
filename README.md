# DOJ FOIA Project

The DOJ FOIA project's larger purpose is to create a single, unified portal for the submission of Freedom of Information Act (FOIA) requests.  This code base's part in that larger goal is to create a Drupal backend which will be accessed by a front-end being developed by [18F](https://18f.gsa.gov).

The backend is currently implemented on the [Lightning distro](https://github.com/acquia/lightning), stealing numerous approaches/configurations from the [Reservoir project](https://github.com/acquia/reservoir)

## BLT

Please see the [BLT documentation](http://blt.readthedocs.io/en/latest/) for information on build, testing, and deployment processes.

### Local setup

Follow steps 1-5 from the [initial setup section](https://docs.acquia.com/blt/developer/onboarding/#initial-set-up) in BLT's docs, as well as 1-3 from the VM steps. Note that you do not need to follow the steps to install npm/nvm on your host machine. If we end up working frontend tasks, these steps can take place within the VM.

VM-related software version suggestions:
- Virtualbox - 6.0.8
- Vagrant - 2.2.2
- Ansible - 2.8.1

Also, be sure to have Vagrant plugins installed:
- vagrant-auto_network (1.0.3, global)
- vagrant-exec (0.5.3, global)
- vagrant-hostmanager (1.8.9, global)
- vagrant-hostsupdater (1.1.1.160, global)
- vagrant-share (1.1.9, global)
- vagrant-vbguest (0.19.0, global)

## Resources

* [Issue queue](https://github.com/18F/beta.foia.gov/issues)
* [GitHub](https://github.com/usdoj/foia)
