# Biller WooCommerce plugin

### Working with translations
- Step 1: To create or update pot file run following command:
```
./biller i18n:make:pot
```
- Step 2: Notify Biller team about newly created/updated translations.
- Step 3: If translations are provided inside .po files, add these files 
and run following command in order to create mo files
```
./biller i18n:make:mo
```
- If translations are provided in CSV or as plain text use Poedit 
visual translation editor tool (https://poedit.net) to create po and mo files.

### Running WooCommerce code sniffer
- Step 1: Run code sniffer:
```
./biller sniffer:run
```
- New file phpcs-report.txt will be created/updated with the errors which should be fixed.
- Step 2: In order to fix reported issues run the following:
```
./biller sniffer:fix
```
- Step 3: Run code sniffer again, and if file phpcs-report.txt still contains some errors, fix these manually.