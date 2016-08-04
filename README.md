# grumphp-bom-task
Force files to have no BOM via GrumPHP
### grumphp.yml:
````yml
parameters:
    tasks:
        aus_bom_fixer:
            triggered_by:  [php, css, scss, less, json, sql, yml, txt]
    extensions:
        - AUS\GrumPHPBomTask\ExtensionLoader
````
### composer:
``composer require --dev andersundsehr/grumphp-bom-task``
