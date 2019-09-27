# DOJ-specific Development Box

## Installation

Internal DOJ developers are not able to run BLT, so will need a different setup
for local development. Local installation will consist of these steps:

1. Clone the foia-api repository in Windows:  
   `git clone https://github.com/usdoj/foia-api.git`  
   `cd foia-api`
2. Manually create a `vendor` folder, and a `geerlingguy` folder:  
   `mkdir -p vendor`  
   `mkdir -p vendor/geerlingguy`
3. Go into the `geerlingguy` folder and clone DrupalVM:  
   `cd vendor/geerlingguy`  
   `git clone https://github.com/geerlingguy/drupal-vm.git`
4. return to the root of the foia-api repository:  
   `cd ../../`  
5. Copy the `local.config.yml` file in this folder into the /box folder:<br/>
   `cp box/doj/local.config.yml box/local.config.yml`
6. Ensure unrestricted internet access (no proxy) and run:  
   `vagrant up`
7. SSH into the VM:<br/>
   `vagrant ssh`
8. Run the Bash script to complete the setup, inside the VM:  
   `bash /vagrant/box/doj/vm-setup.sh`
9. Use drush to setup your local database:<br/>
   `drush sql-sync @foia.test @foia.vm`

## Working within the DOJ network

Working within the DOJ network involves some additional steps which are detailed
in [this private repository](https://github.com/usdoj/vagrant-doj). The files
in that repository will need to be copied into the `/box` folder above, like so:

* `/box/Vagrantfile.local`
* `/box/doj-enterprise.crt`

Also, if you would like to install the items in the local.playbook.yml, put the
following into the Vagrantfile.local file:

```
config.vm.provision :ansible_local do |ansible|
  ansible.playbook = "#{guest_config_dir}/doj/local.playbook.yml"
  ansible.galaxy_role_file = "#{guest_config_dir}/doj/local.requirements.yml"
end
```

### Running tests

Note that running the Behat tests (`blt tests:behat`) will fail if VM has the proxy enabled. To run local tests, first disable the proxy in Vagrantfile.local and reload the VM.

### Updating core and contrib

The simplest way to update core and contrib is: `composer update`. It may be necessary to disable the proxy.

**NOTE**: After updating Drupal core, you may need to re-run the SimpleSAML config placement. To do this, run `blt simplesamlphp:build:config`.
