# DOJ-specific Development Box

Internal DOJ developers are not able to run BLT, so will need a different setup
for local development. Local installation will consist of these steps:

1. Clone the foia-api repository in Windows.
2. Manually create a `vendor` folder, and a `geerlingguy` folder, such as:
   `/path/to/repo/vendor/geerlingguy`
3. Go into the `geerlingguy` folder and clone DrupalVM:
   `git clone https://github.com/geerlingguy/drupal-vm.git`
4. Copy the `local.config.yml` file in this folder into the /box folder:
   `cp /path/to/repo/box/doj/local.config.yml /path/to/repo/box/local.config.yml`
5. Ensure unrestricted internet access (no proxy) and run `vagrant up`.
6. Run `vagrant ssh` to get into the VM.
7. Run `bash /vagrant/box/doj/vm-setup.sh`.
