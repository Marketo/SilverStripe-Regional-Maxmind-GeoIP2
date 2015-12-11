<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A model for storing currency
 */

class MarketoCurrency extends DataObject
{
    private static $db = array(
        'Name' => 'Varchar',
        'Code' => 'Varchar',
        'Number' => 'Varchar',
        'Symbol' => 'Varchar',
        'Thounsands' => 'Varchar',
        'Decimal' => 'Varchar',
        'Format' => 'Varchar'
        
    );
}

class MarketoCurrenctAdmin extends ModelAdmin {
    private static $managed_models = array(
        'MarketoCurrency'
    );

    private static $url_segment = 'currency-admin';

    private static $menu_title = 'Currency Admin';

}
