# DOJ-specific Development Box

Internal DOJ developers are not able to run BLT, so will need a different setup
for local development. Local installation will consist of these steps:

* Copy the `local.config.yml` file in this folder into the /box folder.
* Ensure unrestricted internet access (no proxy) and run `vagrant up`.
* Run `vagrant ssh` to get into the VM.
* Run `bash /vagrant/box/doj/vm-setup.sh`.
