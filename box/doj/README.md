# DOJ-specific Development Box

## Installation

Internal DOJ developers are not able to run BLT, so will need a different setup
for local development. Local installation will consist of these steps:

1. Clone the foia-api repository in Windows:
   `git clone https://github.com/usdoj/foia-api.git`
2. Manually create a `vendor` folder, and a `geerlingguy` folder:
   `mkdir -p /path/to/repo/vendor/geerlingguy`
3. Go into the `geerlingguy` folder and clone DrupalVM:
   `git clone https://github.com/geerlingguy/drupal-vm.git`
4. Copy the `local.config.yml` file in this folder into the /box folder:
   `cp /path/to/repo/box/doj/local.config.yml /path/to/repo/box/local.config.yml`
5. Ensure unrestricted internet access (no proxy) and run:
   `vagrant up`
6. SSH into the VM:
   `vagrant ssh`
7. Run the Bash script to complete the setup, inside the VM:
   `bash /vagrant/box/doj/vm-setup.sh`

## Working within the DOJ network

When firewall and proxy exceptions are finalized, working within the DOJ network
should be a possibility. Note that currently this is not finalized, so this is
not yet feasible. But to eventually set this up, complete these steps:

1. Install these Vagrant plugins: vagrant-proxyconf and vagrant-ca-certificates
2. Download the raw version of [this](https://github.com/usdoj/justicegov/blob/integration/scripts/copy-to-drupalvm-folder/Vagrantfile.local)
   file and place it at `/path/to/repo/box/Vagrantfile.local`.
3. Download the raw version of [this](https://github.com/usdoj/justicegov/blob/integration/scripts/copy-to-drupalvm-folder/doj-enterprise.crt)
   file and place it at `/path/to/repo/box/doj-enterprise.crt`.
4. Reload the VM: `vagrant reload`
