## Running the migrations with updated data
1. Overwrite the data in this module's `data/original` directory with the latest data from the annual scraper in JSON format.
2. `git add` and `git commit` the new data
3. Navigate to this module's `src` directory: `cd src`
4. Run the `data-groomer.php` script to prep the new data files for migration: `php data-groomer.php`
5. `git add` and `git commit` the modified data
6. Enable the module in the environment you'd like to run the migration in: `drush en foia_migrate -y`
7. Import any new agencies: `drush mi agency`
8. Import any new FOIA personnel: `drush mi foia_personnel`
9. Import any new agency components: `drush mi agency_component`
10. Import new processing data, while updating data for existing components: `drush mi processing_data --update`
