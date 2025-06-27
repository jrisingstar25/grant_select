<?php

// Make sure Gravity Forms is active and already loaded.
if (!class_exists('GFForms')) {
    die();
}

// Make sure GrantSelect Search Functionality plugin is active and already loaded.
if (!class_exists('GrantSelectSearchAddOn')) {
    die();
}
require_once plugin_dir_path( __DIR__ ) .'grantselect-search/lib/dompdf/autoload.inc.php';
// reference the Dompdf namespace
use Dompdf\Dompdf;
GFForms::include_feed_addon_framework();

/**
 * GrantSelectRecordsAddOn
 *
 * @copyright   Copyright (c) 2020-2021, GrantSelect
 * @since       1.0
 */
class GrantSelectRecordsAddOn extends GFFeedAddOn {

    protected $_version = GF_GRANTSELECT_RECORDS_ADDON_VERSION;
    protected $_min_gravityforms_version = '2.4.20';
    protected $_slug = 'grantselect-records';
    protected $_path = 'grantselect-records/grantselect-records.php';
    protected $_full_path = __FILE__;
    protected $_title = 'GrantSelect Records Management Functionality';
    protected $_short_title = 'GrantSelect Records Functionality';

    const GRANT_CURRENCY = array(
        'Dollar (US) - USD'              => 'USD', 'Dollar (Canadian) - CAD'       => 'CAD',
        'Afghani - AFA'                  => 'AFA', 'Austral - ARA'                 => 'ARA', 'Baht - THB'                           => 'THB',
        'Balboa - PAB'                   => 'PAB', 'Birr - ETB'                    => 'ETB', 'Bolivar Fuerte - VEF'                 => 'VEF',
        'Boliviano - BOB'                => 'BOB', 'Cedi - GHC'                    => 'GHC', 'Colon (Costa Rican) - CRC'            => 'CRC',
        'Cordoba - NIC'                  => 'NIC', 'Convertible Mark - BAM'        => 'BAM', 'Dalasi - GMD'                         => 'GMD',
        'Dinar (Algerian) - DZD'         => 'DZD', 'Dinar (Bahraini) - BHD'        => 'BHD', 'Dinar (Croatian) - HRD'               => 'HRD',
        'Dinar (Iraqi) - IQD'            => 'IQD', 'Dinar (Jordanian) - JOD'       => 'JOD', 'Dinar (Kuwaiti) - KWD'                => 'KWD',
        'Dinar (Libyan) - LYD'           => 'LYD', 'Dinar (Macedonian) - MKD'      => 'MKD', 'Dinar (Serbian) - CSD'                => 'CSD',
        'Dinar (Tunisian) - TND'         => 'TND', 'Dinar (South Yemeni) - YDD'    => 'YDD', 'Dirham (Moroccan) - MAD'              => 'MAD',
        'Dirham (UAE) - AED'             => 'AED', 'Dobra - STD'                   => 'STD', 'Dollar (Australian) - AUD'            => 'AUD', 'Dollar (Bahamian) - BSD' => 'BSD',
        'Dollar (Barbados) - BBD'        => 'BBD', 'Dollar (Belize) - BZD'         => 'BZD', 'Dollar (Bermudian) - BMD'             => 'BMD',
        'Dollar (Brunei) - BND'          => 'BND', 'Dollar (Cayman Islands) - KYD' => 'KYD', 'Dollar (East Caribbean)'              => 'XCD',
        'Dollar (Fiji) - FJD'            => 'FJD', 'Dollar (Guyana) - GYD'         => 'GYD', 'Dollar (Hong Kong) - HKD'             => 'HKD',
        'Dollar (Jamaican) - JMD'        => 'JMD', 'Dollar (Liberian) - LRD'       => 'LRD', 'Dollar (Malaysian) - MYR'             => 'MYR',
        'Dollar (Namibia) - NAD'         => 'NAD', 'Dollar (New Zealand) - NZD'    => 'NZD', 'Dollar (Singapore) -SGD'              => 'SGD',
        'Dollar (Solomon Islands) - SBD' => 'SBD', 'Dollar (Taiwan, New) - TWD'    => 'TWD', 'Dollar (Trinidad and Tobago) - TTD'   => 'TTD',
        'Dong - VND'                     => 'VND', 'Drachma - GRD'                 => 'GRD', 'Dram - AMD'                           => 'AMD',
        'Ekwele - GQE'                   => 'GQE', 'Escudo (Timorian) - TPE'       => 'TPE',
        'Euro - EUR'                     => 'EUR', 'Forint - HUF'                  => 'HUF',
        'Franc (Central African) - XAF'  => 'XAF', 'Franc (Pacific Franc) - XPF'   => 'XPF', 'Franc (Burundi) - BIF'                => 'BIF',
        'Franc (Comorian) - KMF'         => 'KMF', 'Franc (Djibouti) - DJF'        => 'DJF', 'Franc (Guinea) - GNS'                 => 'GNS',
        'Franc (Malagasy) - MGF'         => 'MGF', 'Franc (Malian) - MLF'          => 'MLF', 'Franc (Rwanda) - RWF'                 => 'RWF',
        'Franc (Swiss) - CHF'            => 'CHF', 'Franc (West African) - XOF'    => 'XOF', 'Gourde - HTG'                         => 'HTG',
        'Guarani - PYG'                  => 'PYG', 'Guilder (Aruban) - AWG'        => 'AWG', 'Guilder (Netherlands Antilles) - ANG' => 'ANG',
        'Guilder (Surinam) - SRG'        => 'SRG', 'Hryvna - UAH'                  => 'UAH', 'Inti - PEI'                           => 'PEI',
        'Kina - PGK'                     => 'PGK', 'Kip - LAK'                     => 'LAK', 'Krona (Icelandic) - ISK'              => 'ISK',
        'Krona (Swedish) - SEK'          => 'SEK', 'Krone (Danish) - DKK'          => 'DKK', 'Krone (Norwegian) - NOK'              => 'NOK',
        'Kuna - HRK'                     => 'HRK', 'Kwacha (Malawian) - MWK'       => 'MWK', 'Kwacha (Zambian) - ZMK'               => 'ZMK',
        'Kwanza - AOA'                   => 'AOA', 'Kyat - MMK'                    => 'MMK', 'Lari - GEL'                           => 'GEL',
        'Lek - ALL'                      => 'ALL', 'Lempira - HNL'                 => 'HNL', 'Leone - SLL'                          => 'SLL',
        'Leu (Moldavian) - MDL'          => 'MDL', 'Leu (Romanian) - ROL'          => 'ROL', 'Lev - BGL'                            => 'BGL',
        'Lilangeni - SZL'                => 'SZL', 'Litas - LTL'                   => 'LTL', 'Loti - LSL'                           => 'LSL',
        'Manat (Azerbaijani) - AZM'      => 'AZM', 'Manat (Turkmenistani) - TMM'   => 'TMM', 'Maloti - LSM'                         => 'LSM',
        'Metical - MZM'                  => 'MZM', 'Nakfa - ERN'                   => 'ERN', 'Naira - NGN'                          => 'NGN',
        'New Lira (Turkish) - TRY'       => 'TRY', 'Nuevo Peso - ARS'              => 'ARS', 'New Peso (Mexican) - MXN'             => 'MXN',
        'New Sol - PEN'                  => 'PEN', 'New Zloty - PLN'               => 'PLN', 'Ngultrum - BTN'                       => 'BTN',
        'Ouguiya - MRO'                  => 'MRO', 'Pa\'anga - TOP'                => 'TOP', 'Pataca - MOP'                         => 'MOP',
        'Peso (Bolivian) - BOP'          => 'BOP', 'Peso (Chilean) - CLP'          => 'CLP', 'Peso (Colombian) - COP'               => 'COP',
        'Peso (Cuban) - CUP'             => 'CUP', 'Peso(Dominican Republic)- DOP' => 'DOP', 'Peso (Guinea-Bissau) - GWP'           => 'GWP',
        'Peso (Philippines)- PHP'        => 'PHP', 'Peso (Uruguayan) - UYU'        => 'UYU', 'Pound (Egytian) - EGP'                => 'EGP',
        'Pound (Falkland) - FKP'         => 'FKP', 'Pound (Gibraltar) - GIP'       => 'GIP', 'Pound (Lebanese) - LBP'               => 'LBP',
        'Pound (St Helena) - SHP'        => 'SHP', 'Pound (Sterling) - GBP'        => 'GBP', 'Pound (Sudanese) - SDG'               => 'SDG',
        'Pound (Syrian) - SYP'           => 'SYP', 'Pula - BWP'                    => 'BWP', 'Quetzal - GTQ'                        => 'GTO',
        'Rand - ZAR'                     => 'ZAR', 'Rand (financial) - ZAL'        => 'ZAL', 'Real - BRL'                           => 'BRL',
        'Rial (Iranian) - IRR'           => 'IRR', 'Rial (Omani) - OMR'            => 'OMR', 'Riel - KHR'                           => 'KHR',
        'Ringgit - MYR'                  => 'MYR', 'Riyal (Qatari) - QAR'          => 'QAR', 'Riyal (Saudi) - SAR'                  => 'SAR',
        'Riyal (Yemeni) - YER'           => 'YER', 'Rouble (Belarussian) - BYR'    => 'BYR', 'Rouble (Russian Federation) - RUR'    => 'RUR',
        'Rouble (Tajik) - TJR'           => 'TJR', 'Rufiyaa - MVR'                 => 'MVR', 'Rupee (Indian) - INR'                 => 'INR',
        'Rupee (Mauritius) - MUR'        => 'MUR', 'Rupee (Nepalese) - NPR'        => 'NPR', 'Rupee (Pakistani) - PKR'              => 'PKR',
        'Rupee (Seychelles) - SCR'       => 'SCR', 'Rupee (Sri Lankan) - LKR'      => 'LKR', 'Rupiah - IDR'                         => 'IDR',
        'Shekel - ILS'                   => 'ILS', 'Shilling (Kenyan) - KES'       => 'KES', 'Shilling (Somali) - SOS'              => 'SOS',
        'Shilling (Tanzanian) - TZS'     => 'TZS', 'Shilling (Ugandan) - UGS'      => 'USG', 'Som (Kyrgyzstani) - KGS'              => 'KGS',
        'Som (Uzbekistani) - UZS'        => 'UZS', 'Syli - GNS'                    => 'GNS', 'Taka - BDT'                           => 'BDT',
        'Tala - WST'                     => 'WST', 'Tenge - KZT'                   => 'KZT', 'Tugrik - MNT'                         => 'MNT',
        'Unidades de Fomento - CLF'      => 'CLF', 'Vatu - VUV'                    => 'VUV', 'Won (North Korean) - KPW'             => 'KPW',
        'Won (South Korean) - KRW'       => 'KRW', 'Yen - JPY'                     => 'JPY', 'Yuan Renminbi - CNY'                  => 'CNY',
        'Zaire (New) - CDZ'              => 'CDZ'
    );

    /**
     * @var object|null $_instance If available, contains an instance of this class.
     */
    private static $_instance = null;

    /**
     * Returns an instance of this class, and stores it in the $_instance property.
     *
     * @return object $_instance An instance of this class.
     */
    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * @param $feed
     * @param $entry
     * @param $form
     */
    public function process_feed($feed, $entry, $form )
    {
        // this is for adding meta-data to the entry
        return $entry;
    }
    public function make_pdf_html($grant_details){
        if ( !current_user_can('view_grants') ) {
            ob_start();
            ?>
            <div id="content_wrapper">
                <div id="content_left">
                    <!-- Intro Text -->
                    <div id="intro">
                        <div id="notice-label" class="error">
                            Sorry, you are not authorized to view grant data.
                        </div>
                    </div>
                    <!--intro-->
                </div>
            </div>
            <?php
            $res = ob_get_contents();
            ob_end_clean();

            return $res;
        }

        $page_number = 1; //test

        $grant_id = $grant_details->id;
        $revisit_date = self::get_revisit_date( $grant_id );

        ob_start();
        ?>

       

        <!-- Intro Text -->
        <div id="intro">
            <?php if ( !(empty($grant_details)) ) : ?>
                <h2><?=stripslashes($grant_details->title);?></h2>
                <ul class="meta-info">
                    <?php
                    if ( !empty($grant_details->updated_at) ) {
                        list ($date, $time) = explode (" ", $grant_details->updated_at, 2);
                        list ($year, $month, $day) = explode ("-", $date);
                        list ($hour, $minute, $second) = explode (":", $time);
                        $updated_at_date = date("m/d/Y", mktime($hour, $minute, $second, $month, $day, $year));
                        $updated_at_time = date("g:i a T", mktime($hour, $minute, $second, $month, $day, $year));
                    } elseif ( !empty($grant_details->created_at) ) {
                        list($date, $time) = explode(" ", $grant_details->created_at, 2);
                        list ($year, $month, $day) = explode ("-", $date);
                        list ($hour, $minute, $second) = explode (":", $time);
                        $updated_at_date = date("m/d/Y", mktime($hour, $minute, $second, $month, $day, $year));
                        $updated_at_time = date("g:i a T", mktime($hour, $minute, $second, $month, $day, $year));
                    } else {
                        $updated_at = "(unknown)";
                    }
                    ?>
                    <li>Last updated on <?= $updated_at_date ?> at <?= $updated_at_time ?></li>
                    <?php
                    if ( !empty($grant_details->last_editor) ) {
                        $updated_by = $grant_details->last_editor[0]->display_name;
                    } else {
                        $updated_by = "(unknown)";
                    }
                    ?>
                    <li>Last updated by <?= $updated_by ?></li>
                    <li>Status:  <?php
                        if ( $grant_details->status == "A"  ){
                            echo "Active";
                        } elseif( $grant_details->status == "S"  ){
                            echo "Suspended";
                        } elseif( $grant_details->status == "P"  ){
                            echo "Pending";
                        } elseif( $grant_details->status == "R"  ){
                            echo "Ready for Review";
                        } elseif( $grant_details->status == "D"  ){
                            echo "Deleted";
                        } elseif( $grant_details->status == "E"  ){
                            echo "Error";
                        }
                        ?></li>
                </ul>
            <?php else : ?>

                <div class="tabbed_wrapper">
                    <h2 id="tabbed_header">Record not found.</h2>
                    <div class="tabbed_header">
                        <p>There is no grant record with id = <span class="gid"><?=$grant_id?></span> in the database. Please try a different record number.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div><!--intro-->


        <?php if ( !(empty($grant_details)) ) : ?>
            <!-- Sponsor info -->
            <div class="tabbed_wrapper">
                <h2 id="tabbed_header">Sponsor Info</h2>
                <div class="tabbed_header">

                    <?php if ( !empty($grant_details->sponsor) ) : ?>
                        <?php foreach ($grant_details->sponsor as $k=>$v) : ?>
                            <p>
                                <?php if ( !empty($v->sponsor_name) ) : ?>
                                    <?= stripslashes($v->sponsor_name); ?><br />
                                <?php endif; ?>

                                <?php if ( !empty($v->sponsor_department) ) : ?>
                                    <?= stripslashes($v->sponsor_department); ?><br />
                                <?php endif ?>

                                <?php if ( !empty($v->sponsor_address) and (!empty($v->sponsor_address2)) ) : ?>
                                    <? stripslashes($v->sponsor_address); ?>,
                                <?php endif; ?>

                                <?php if ( !empty($v->sponsor_address) and (empty($v->sponsor_address2)) ) : ?>
                                    <? stripslashes($v->sponsor_address); ?>
                                <?php endif; ?>

                                <?php if ( !empty($v->sponsor_address2) ) : ?>
                                    <?= stripslashes($v->sponsor_address2); ?>
                                <?php endif; ?>

                                <?php if ( !empty($v->sponsor_address) || !empty($v->sponsor_address2) ) : ?><br /><?php endif; ?>

                                <?php if ( !empty($v->sponsor_city) ):?><?= stripslashes($v->sponsor_city); ?><?php endif;?><?php if ( !empty($v->sponsor_city) && !empty($v->sponsor_state) ) : ?>,<?php endif; ?>

                                <?php if ( !empty($v->sponsor_state) ) : ?>
                                    <?= stripslashes($v->sponsor_state); ?>
                                <?php endif; ?>

                                <?php if( !empty($v->sponsor_zip) ) : ?>
                                    <?= stripslashes($v->sponsor_zip); ?><br />
                                <?php endif; ?>

                                <?php if ( !empty($v->sponsor_country) ) : ?>
                                    <?= stripslashes($v->sponsor_country); ?><br />
                                <?php endif; ?>

                                Website:
                                <?php if ( !empty($v->sponsor_url) ) : ?>
                                    <a href="<?= stripslashes($v->sponsor_url); ?>" target="_blank">
                                        <?= stripslashes($v->sponsor_url); ?></a>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                                <br />

                                Type:
                                <?php if ( !empty($v->sponsor_type) ) : ?>
                                    <?= stripslashes($v->sponsor_type); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div><!-- end sponsor info -->

            <!-- Grant program info -->
            <div class="tabbed_wrapper">

                <h2 id="tabbed_header">Grant Info</h2>
                <div class="tabbed_header">
                    <h3 class="grant_title"><?= stripslashes($grant_details->title); ?></h3>

                    <div class="grant-details">
                        <h3>Grant URL</h3>
                        <?php if ( !empty($grant_details->grant_url_1) ) : ?>
                            <a href="<?php echo $grant_details->grant_url_1; ?>" target="_blank"><?php echo $grant_details->grant_url_1; ?></a>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Amount</h3>
                        <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                            <?=number_format($grant_details->amount_min); ?> - <?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                        <?php endif; ?>

                        <?php if (empty($grant_details->amount_min) && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                            <?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                        <?php endif; ?>

                        <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min == '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                            Up to&nbsp;<?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                        <?php endif; ?>

                        <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && empty($grant_details->amount_max)) : ?>
                            <?=number_format($grant_details->amount_min); ?> <?=$grant_details->amount_currency; ?>
                        <?php endif; ?>

                        <?php if(!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max == '0.00') : ?>
                            Up to&nbsp;<?=number_format($grant_details->amount_min); ?> <?=$grant_details->amount_currency; ?>
                        <?php endif; ?>

                        <?php if (empty($grant_details->amount_min) && empty($grant_details->amount_max)) : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Description</h3>
                        <?php if(!empty($grant_details->description)) : ?>
                            <?=nl2br(stripslashes($grant_details->description)); ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Requirements</h3>
                        <?php if(!empty($grant_details->requirements)) : ?>
                            <?=nl2br(stripslashes($grant_details->requirements)); ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Geographic Focus</h3>
                        <?php if (!empty($grant_details->geo_location)): ?>
                            <?php foreach($grant_details->geo_location as $k=>$v):?>
                                <?= stripslashes($v->geo_location); ?><br>
                            <?php endforeach;?>
                        <?php else: ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Restrictions</h3>
                        <?php if(!empty($grant_details->restrictions)) : ?>
                            <?=nl2br(stripslashes($grant_details->restrictions)); ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                    <div class="grant-details">
                        <h3>Samples</h3>
                        <?php if(!empty($grant_details->samples)) : ?>
                            <?=nl2br(stripslashes($grant_details->samples)); ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </div>

                </div>

                <div class="clear"></div>

            </div><!-- end grant program info -->

            <!-- Contact info -->
            <div class="tabbed_wrapper">
                <h2 id="tabbed_header">Contact Info</h2>
                <div class="tabbed_header">

                    <?php if(!empty($grant_details->contact_info)) : ?>
                        <?php foreach($grant_details->contact_info as $k=>$v) : ?>
                            <p>
                                <?php if(!empty($v->contact_name)):?><?= stripslashes($v->contact_name); ?><?php endif;?><?php if(!empty($v->contact_title) && !empty($v->contact_name)):?>, <?php endif;?>
                                <?php if(!empty($v->contact_title)) : ?><?= stripslashes($v->contact_title); ?>
                                <?php endif; ?>

                                <?php if(!empty($v->contact_title) || !empty($v->contact_name)) : ?><br /><?php endif; ?>

                                <?php if(!empty($v->contact_org_dept)) : ?>
                                    <?= stripslashes($v->contact_org_dept); ?><br />
                                <?php endif; ?>

                                <?php if(!empty($v->contact_address1) and !empty($v->contact_address2)) : ?>
                                    <?= stripslashes($v->contact_address1); ?>,
                                <?php endif; ?>

                                <?php if(!empty($v->contact_address1) and empty($v->contact_address2)) : ?>
                                    <?= stripslashes($v->contact_address1); ?>
                                <?php endif; ?>

                                <?php if(!empty($v->contact_address2)) : ?>
                                    <?= stripslashes($v->contact_address2); ?>
                                <?php endif; ?>

                                <?php if(!empty($v->contact_address1) || !empty($v->contact_address2)) : ?><br /><?php endif;?>

                                <?php if(!empty($v->contact_city)) : ?>
                                    <?= stripslashes($v->contact_city); ?>,
                                <?php endif; ?>

                                <?php if(!empty($v->contact_state)) : ?>
                                    <?= stripslashes($v->contact_state); ?>
                                <?php endif; ?>

                                <?php if(!empty($v->contact_zip)) : ?>
                                    <?= stripslashes($v->contact_zip); ?>
                                <?php endif; ?>
                                <?php if (!empty($v->contact_city) || !empty($v->contact_state) || !empty($v->contact_zip)) : ?><br /><?php endif; ?>

                                <?php if(!empty($v->country)) : ?>
                                    <?= stripslashes($v->country); ?><br />
                                <?php endif; ?>

                                <?php
                                $phones = '';
                                if (!empty($v->contact_phone_1)) {
                                    $phones = $v->contact_phone_1;
                                }
                                if (!empty($v->contact_phone_2)) {
                                    if ($phones != '') {
                                        $phones .= ', ' . $v->contact_phone_2;
                                    }
                                    else {
                                        $phones = $v->contact_phone_2;
                                    }
                                }
                                echo $phones . '<br />';
                                ?>


                                <?php if(!empty($v->contact_fax)) : ?>
                                    fax: <?=$v->contact_fax; ?><br />
                                <?php endif; ?>
                                <?php $email = ''; ?>
                                <?php if (!empty($v->contact_email_1)) : ?>
                                    <?php $email = '<a href="mailto:'. $v->contact_email_1 . '">' . $v->contact_email_1 . '</a>' ?>
                                <?php endif; ?>
                                <?php if (!empty($v->contact_email_2) and $email != '') : ?>
                                    <?php $email .= ', ' . '<a href="mailto:'. $v->contact_email_2 . '">' . $v->contact_email_2 . '</a>' ?>
                                <?php elseif (!empty($v->contact_email_2) and $email == '') : ?>
                                    <?php $email = '<a href="mailto:'. $v->contact_email_2 . '">' . $v->contact_email_2 . '</a>' ?>
                                <?php endif; ?>
                                <?php if ($email != '') : ?>
                                    email: <?= $email ?>
                                <?php endif; ?>
                            </p>
                        <?php endforeach;?>
                    <?php endif;?>

                </div>

            </div><!-- end sponsor info -->

        <?php endif; ?>
<?php
        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }
    // function for managing grant records
    public function grantselect_record_manager( $atts ) {

        $current_user = get_current_user_id();

        $action         = filter_var( $atts['action'], FILTER_SANITIZE_STRING );
        $submitted_data = stripslashes_deep($_POST);

        $display_content = '<div class="records-manager error">Error</div>';

        switch ($action) {
            case 'view_record':
                $record_num = absint( $_GET['gid'] );
                if ( empty($record_num) ) $record_num = self::grantselect_get_lowest_grant_id();
                $display_content = '<form class="find-record" action="" method="get">';
				$display_content .= '<h2>View Grant Record #';
				$display_content .= '<input type="text" name="gid" size="5" value="' . $record_num . '">';
                $display_content .= '<input type="submit" value="Find">';
                $display_content .= '</h2></form>';
                $grant_details = GrantSelectSearchAddOn::grantselect_get_grant_details( $record_num, 'editor' );
                if ( empty ($grant_details) ) {
                    $display_content .= self::grantselect_display_grant_not_found();
                } else {
                    
                    if (isset($_GET['download'])){
                        if ($_GET['download'] == "pdf"){
                            ob_end_clean();
                            header('Content-type: application/pdf');
                            header('Content-Disposition: attachment; filename="detail'.$record_num.'.pdf"');
                            header('Cache-Control: max-age=0');
                            $dompdf = new Dompdf();

                            $dompdf->loadHtml(self::make_pdf_html( $grant_details ));
                            
                            // (Optional) Setup the paper size and orientation
                            $dompdf->setPaper('A4', 'portrait');

                            // Render the HTML as PDF
                            $dompdf->render();

                            // Output the generated PDF to Browser
                            $dompdf->stream();
                            
                            exit;
                        }
                    }else{
                        $display_content .= self::grantselect_display_grant_details( $grant_details );
                    }
                }
                break;
            case 'edit_record':

//                echo "<pre>";
//                print_r($submitted_data);
//                echo "</pre>";

                $data_array = '';
                if ( $submitted_data['action'] == 'edit' ) {
                    $grant_id = $submitted_data['grant']['id'];
                    $edit_result = self::update_grant_record( $grant_id, $submitted_data );
                    if ( !$edit_result['success'] ) {
                        $user_notice['msg'] = $edit_result['msg'];
                        $user_notice['class'] = "error";
                        $data_array = $submitted_data;
                    } else {
                        $user_notice['msg'] = $edit_result['msg'];
                        $user_notice['class'] = "success";
                    }
                }

                $record_num = absint( $_GET['gid'] );
                if ( empty($record_num) ) $record_num = self::grantselect_get_lowest_grant_id();
                $display_content = '<form class="find-record" action="" method="get">';
                $display_content .= '<h2>Edit Grant Record #';
                $display_content .= '<input type="text" name="gid" size="5" value="' . $record_num . '">';
                $display_content .= '<input type="submit" value="Find">';
                $display_content .= '</h2></form>';
                if (isset($_GET['uri'])){
                    $display_content .= '<ul><li class="actions"><a href="/editor/view/?gid=' . $record_num . '&uri=' . urlencode($_GET['uri']) . '">View</a></li></ul>';
                }else{
                    $display_content .= '<ul><li class="actions"><a href="/editor/view/?gid=' . $record_num . '">View</a></li></ul>';
                }
                
                $grant_details = GrantSelectSearchAddOn::grantselect_get_grant_details( $record_num, 'editor' );
                if ( empty ($grant_details) ) {
                    $display_content .= self::grantselect_display_grant_not_found();
                } else {
                    $sponsor_list = self::get_sponsor_list();
                    $sponsor_type_list = self::get_sponsor_type_list();
                    $segment_list = self::get_segment_list();
                    $geo_locations_domestic_list = GrantSelectSearchAddOn::get_geo_locations_list('domestic');
                    $geo_locations_foreign_list = GrantSelectSearchAddOn::get_geo_locations_list('foreign');
                    $programs_list = self::get_programs_list();
                    $subjects_list = GrantSelectSearchAddOn::get_subjects_list();
                    $target_populations_list = self::get_target_populations_list();
                    $display_content .= self::grantselect_display_grant_edit($grant_details, $user_notice, $sponsor_list, $sponsor_type_list, $segment_list, $geo_locations_domestic_list, $geo_locations_foreign_list, $programs_list, $subjects_list, $target_populations_list);
                }
                break;
            case 'add_record':
                $data_array = '';
                if ( $submitted_data['action'] == 'add' ) {
                    $add_result = self::add_grant_record( $submitted_data );
                    if ( !$add_result['success'] ) {
                        $user_notice['msg'] = $add_result['msg'];
                        $user_notice['class'] = "error";

                        // If failed, marked as status=E (error) in database
                        if ( !empty($add_result['grant_id']) ) {
                            self::update_grant_status( $add_result['grant_id'], "E" );
                        }

                        $data_array = $submitted_data;
                    } else {
                        $user_notice['msg'] = $add_result['msg'];
                        $user_notice['class'] = "success";
                    }
                }

                $grant_details = GrantSelectSearchAddOn::grantselect_get_grant_details( '', 'editor', $data_array );

                $sponsor_list = self::get_sponsor_list();
                $sponsor_type_list = self::get_sponsor_type_list();
                $segment_list = self::get_segment_list();
                $geo_locations_domestic_list = GrantSelectSearchAddOn::get_geo_locations_list('domestic');
                $geo_locations_foreign_list = GrantSelectSearchAddOn::get_geo_locations_list('foreign');
                $programs_list = self::get_programs_list();
                $subjects_list = GrantSelectSearchAddOn::get_subjects_list();
                $target_populations_list = self::get_target_populations_list();
                $display_content = self::grantselect_display_grant_edit( $grant_details, $user_notice, $sponsor_list, $sponsor_type_list, $segment_list, $geo_locations_domestic_list, $geo_locations_foreign_list, $programs_list, $subjects_list, $target_populations_list );
                break;
        }

        return $display_content;
    }


    /**
     * Function get_sponsor_list
     * @return $sponsor_list
     */
    function get_sponsor_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, sponsor_name, sponsor_department, sponsor_address, sponsor_address2, sponsor_city,
                             sponsor_country, sponsor_state, sponsor_zip, sponsor_url, grant_sponsor_type_id
                        FROM " . $wpdb->prefix . "gs_grant_sponsors
                        WHERE sponsor_name !='' AND status='A'
                        ORDER BY sponsor_name ASC, sponsor_department ASC";
        $sql = $wpdb->prepare( $sql_query );
        $sponsor_list_raw = $wpdb->get_results( $sql, 'ARRAY_A' );

//        echo "<pre>";
//        print_r($sponsor_list_raw);
//        echo "</pre>";

        foreach ($sponsor_list_raw as $key=>$value ) {
            $sponsor_info = $value['id'] . '@' . stripslashes($value['sponsor_name']) . '@' . stripslashes($value['sponsor_department']) . '@' .
                stripslashes($value['sponsor_address']) . '@' . stripslashes($value['sponsor_address2']) . '@' . stripslashes($value['sponsor_city']) . '@' .
                stripslashes($value['sponsor_country']) . '@' . stripslashes($value['sponsor_state']) . '@' . stripslashes($value['sponsor_zip']) . '@' .
                $value['sponsor_url'] . '@' . $value['grant_sponsor_type_id'];
            $sponsor_list[$value['id']] = array(
                'sponsor_info'       => stripslashes($sponsor_info),
                'sponsor_name'       => stripslashes($value['sponsor_name']),
                'sponsor_department' => stripslashes($value['sponsor_department']),
            );
        }

//        echo "<pre>";
//        echo $sql . "\n\n";
//        print_r($sponsor_list);
//        echo "</pre>";

        return $sponsor_list;
    }


    /**
     * Function get_sponsor_type_list
     * @return $sponsor_type_list
     */
    function get_sponsor_type_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, sponsor_type FROM " . $wpdb->prefix . "gs_grant_sponsor_types
                        WHERE sponsor_type !=''
                        ORDER BY sponsor_type ASC";
        $sql = $wpdb->prepare( $sql_query );

        $sponsor_type_list = $wpdb->get_results( $sql );

        return $sponsor_type_list;
    }


    /**
     * Function get_segment_list
     * @return $segment_list
     */
    function get_segment_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, segment_title FROM " . $wpdb->prefix . "gs_grant_segments
                        WHERE segment_title !=''
                        ORDER BY segment_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $segment_list = $wpdb->get_results( $sql );

if (0) {
        echo "<pre>";
        echo $sql . "\n\n";
        print_r($segment_list);
        echo "</pre>";
}
        return $segment_list;
    }


    /**
     * Function get_programs_list
     * @return $programs_list
     */
    function get_programs_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, program_title FROM " . $wpdb->prefix . "gs_grant_programs
                        WHERE program_title !=''
                        ORDER BY program_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $programs_list = $wpdb->get_results( $sql );

//        echo "<pre>";
//        echo $sql . "\n\n";
//        print_r($programs_list);
//        echo "</pre>";

        return $programs_list;
    }


    /**
     * Function get_target_populations_list
     * @return $target_populations_list
     */
    function get_target_populations_list()
    {
        global $wpdb;

        $sql_query = "SELECT id, target_title FROM " . $wpdb->prefix . "gs_grant_targets
                        WHERE target_title !=''
                        ORDER BY target_title ASC";
        $sql = $wpdb->prepare( $sql_query );
        $target_populations_list = $wpdb->get_results( $sql );

//        echo "<pre>";
//        echo $sql . "\n\n";
//        print_r($target_populations_list);
//        echo "</pre>";

        return $target_populations_list;
    }


    /**
     * Function get_revisit_date
     * Returns year, month, day of most recently updated revisit key date
     * @params $grant_id
     * @return object $revisit_date
     */
    function get_revisit_date( $grant_id )
    {
        global $wpdb;

        $sql_query = "SELECT id, date_title, year, month, date FROM " . $wpdb->prefix . "gs_grant_key_dates
                        WHERE date_title LIKE 'revisit' AND grant_id = %d
                        ORDER BY updated_at DESC";
        $sql = $wpdb->prepare( $sql_query, $grant_id );
        $revisit_dates = $wpdb->get_results( $sql );

//        echo "<pre>";
//        echo $sql . "\n\n";
//        print_r($revisit_dates);
//        echo "</pre>";

        return $revisit_dates[0];
    }


    /**
     * Function update_grant_status
     * Updates the grant status in the database
     * @params $grant_id, $status_str
     * @return true on success, false on fail
     */
    function update_grant_status( $grant_id, $status_str )
    {
        if ( !current_user_can('edit_grants') ) {
            return false;
        }

        global $wpdb;

        if ( empty ($grant_id) || empty ($status_str) ) {
            return false;
        }

        $current_time = date("Y-m-d H:i:s");
        $table = $wpdb->prefix . 'gs_grants';

        $format         = array( '%s','%s' );
        $where_format   = array( '%d' );
        $data = array(
            'status'                => $status_str,
            'updated_at'            => $current_time
        );
        $where = array(
            'id'                    => $grant_id,
        );
        if ( $wpdb->update( $table, $data, $where, $format, $where_format ) ) {
            return true;
        } else {
            return false;
        }
    }


//    /**
//     * Function array_to_grant_object
//     * Converts data submitted in array format to a grant object
//     * @params  $submitted_data
//     *          $data_mode
//     * @return object $grant_details
//     */
//    function array_to_grant_object( $submitted_data, $data_mode )
//    {
//        $grant_details = GrantSelectSearchAddOn::grantselect_get_grant_details( '', $data_mode );   //get empty grant object
//
//
////        echo "<pre>";
////        echo $sql . "\n\n";
////        print_r($revisit_dates);
////        echo "</pre>";
//
//        return $grant_details;
//    }


    /**
     * Function grantselect_display_grant_details
     * Outputs grant record details for display on website
     * @params $grant_details
     * @return $res
     */
    function grantselect_display_grant_details( $grant_details ) {

        if ( !current_user_can('view_grants') ) {
            ob_start();
            ?>
            <div id="content_wrapper">
                <div id="content_left">
                    <!-- Intro Text -->
                    <div id="intro">
                        <div id="notice-label" class="error">
                            Sorry, you are not authorized to view grant data.
                        </div>
                    </div>
                    <!--intro-->
                </div>
            </div>
            <?php
            $res = ob_get_contents();
            ob_end_clean();

            return $res;
        }

//        echo "<pre>";
//        print_r($grant_details);
//        echo "</pre>";

        //todo: $page_number
        $page_number = 1; //test

        $grant_id = $grant_details->id;
        $revisit_date = self::get_revisit_date( $grant_id );

        ob_start();
        ?>

        <div id="content_wrapper">
            <div id="content_left">

                <!-- Intro Text -->
                <div id="intro">
                    <?php if ( !(empty($grant_details)) ) : ?>
                        <h2><?=stripslashes($grant_details->title);?></h2>
                        <?php if (!isset($_GET['download'])){ ?>
                            <ul class="actions">
                                <li><a href="/editor/records/edit/?gid=<?=$grant_id?>">Edit</a></li>
                                <li><a href="/editor/records/view/?download=pdf&gid=<?=$grant_id?>">PDF Download</a></li>
                            <?php if (!empty($page_number)) { ?>
                                <li>
                                    <?php if (isset($_GET['uri'])):?>
                                        <a href="<?=$_GET['uri'];?>">
                                        <?php if (strpos($_GET['uri'], '/editor/search/results/?') !== false):?>
                                            Back to search results
                                        <?php else:?>
                                            Back to report
                                        <?php endif;?>
                                        </a>
                                    <?php else:?>
                                        <a href="javascript:history.back();">Back to search results</a>
                                    <?php endif;?>
                                </li>
                            <?php } ?>
                            </ul>
                        <?php } ?>
                        <ul class="meta-info">
                            <?php
                            if ( !empty($grant_details->updated_at) ) {
                                list ($date, $time) = explode (" ", $grant_details->updated_at, 2);
                                list ($year, $month, $day) = explode ("-", $date);
                                list ($hour, $minute, $second) = explode (":", $time);
                                $updated_at_date = date("m/d/Y", mktime($hour, $minute, $second, $month, $day, $year));
                                $updated_at_time = date("g:i a T", mktime($hour, $minute, $second, $month, $day, $year));
                            } elseif ( !empty($grant_details->created_at) ) {
                                list($date, $time) = explode(" ", $grant_details->created_at, 2);
                                list ($year, $month, $day) = explode ("-", $date);
                                list ($hour, $minute, $second) = explode (":", $time);
                                $updated_at_date = date("m/d/Y", mktime($hour, $minute, $second, $month, $day, $year));
                                $updated_at_time = date("g:i a T", mktime($hour, $minute, $second, $month, $day, $year));
                            } else {
                                $updated_at = "(unknown)";
                            }
                            ?>
                            <li>Last updated on <?= $updated_at_date ?> at <?= $updated_at_time ?></li>
                            <?php
                            if ( !empty($grant_details->last_editor) ) {
                                $updated_by = $grant_details->last_editor[0]->display_name;
                            } else {
                                $updated_by = "(unknown)";
                            }
                            ?>
                            <li>Last updated by <?= $updated_by ?></li>
                            <li>Status:  <?php
                                if ( $grant_details->status == "A"  ){
                                    echo "Active";
                                } elseif( $grant_details->status == "S"  ){
                                    echo "Suspended";
                                } elseif( $grant_details->status == "P"  ){
                                    echo "Pending";
                                } elseif( $grant_details->status == "R"  ){
                                    echo "Ready for Review";
                                } elseif( $grant_details->status == "D"  ){
                                    echo "Deleted";
                                } elseif( $grant_details->status == "E"  ){
                                    echo "Error";
                                }
                                ?></li>
                        </ul>
                    <?php else : ?>

                        <div class="tabbed_wrapper">
                            <h2 id="tabbed_header">Record not found.</h2>
                            <div class="tabbed_header">
                                <p>There is no grant record with id = <span class="gid"><?=$grant_id?></span> in the database. Please try a different record number.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div><!--intro-->


                <?php if ( !(empty($grant_details)) ) : ?>
                    <!-- Sponsor info -->
                    <div class="tabbed_wrapper">
                        <h2 id="tabbed_header">Sponsor Info</h2>
                        <div class="tabbed_header">

                            <?php if ( !empty($grant_details->sponsor) ) : ?>
                                <?php foreach ($grant_details->sponsor as $k=>$v) : ?>
                                    <p>
                                        <?php if ( !empty($v->sponsor_name) ) : ?>
                                            <?= stripslashes($v->sponsor_name); ?><br />
                                        <?php endif; ?>

                                        <?php if ( !empty($v->sponsor_department) ) : ?>
                                            <?= stripslashes($v->sponsor_department); ?><br />
                                        <?php endif ?>

                                        <?php if ( !empty($v->sponsor_address) and (!empty($v->sponsor_address2)) ) : ?>
                                            <?= stripslashes($v->sponsor_address); ?>,
                                        <?php endif; ?>

                                        <?php if ( !empty($v->sponsor_address) and (empty($v->sponsor_address2)) ) : ?>
                                            <?= stripslashes($v->sponsor_address); ?>
                                        <?php endif; ?>

                                        <?php if ( !empty($v->sponsor_address2) ) : ?>
                                            <?= stripslashes($v->sponsor_address2); ?>
                                        <?php endif; ?>

                                        <?php if ( !empty($v->sponsor_address) || !empty($v->sponsor_address2) ) : ?><br /><?php endif; ?>

                                        <?php if ( !empty($v->sponsor_city) ):?><?= stripslashes($v->sponsor_city); ?><?php endif;?><?php if ( !empty($v->sponsor_city) && !empty($v->sponsor_state) ) : ?>,<?php endif; ?>

                                        <?php if ( !empty($v->sponsor_state) ) : ?>
                                            <?= stripslashes($v->sponsor_state); ?>
                                        <?php endif; ?>

                                        <?php if( !empty($v->sponsor_zip) ) : ?>
                                            <?= stripslashes($v->sponsor_zip); ?><br />
                                        <?php endif; ?>

                                        <?php if ( !empty($v->sponsor_country) ) : ?>
                                            <?= stripslashes($v->sponsor_country); ?><br />
                                        <?php endif; ?>

                                        Website:
                                        <?php if ( !empty($v->sponsor_url) ) : ?>
                                            <a href="<?=$v->sponsor_url;?>" target="_blank">
                                                <?=$v->sponsor_url;?></a>
                                        <?php else : ?>
                                            (not specified)
                                        <?php endif; ?>
                                        <br />

                                        Type:
                                        <?php if ( !empty($v->sponsor_type) ) : ?>
                                            <?= stripslashes($v->sponsor_type); ?>
                                        <?php else : ?>
                                            (not specified)
                                        <?php endif; ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div><!-- end sponsor info -->

                    <!-- Grant program info -->
                    <div class="tabbed_wrapper">

                        <h2 id="tabbed_header">Grant Info</h2>
                        <div class="tabbed_header">
                            <h3 class="grant_title"><?= stripslashes($grant_details->title); ?></h3>

                            <div class="grant-details">
                                <h3>Grant URL</h3>
                                <?php if ( !empty($grant_details->grant_url_1) ) : ?>
                                    <a href="<?php echo $grant_details->grant_url_1; ?>" target="_blank"><?php echo $grant_details->grant_url_1; ?></a>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Amount</h3>
                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    <?=number_format($grant_details->amount_min); ?> - <?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (empty($grant_details->amount_min) && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    <?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min == '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max != '0.00') : ?>
                                    Up to&nbsp;<?=number_format($grant_details->amount_max); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && empty($grant_details->amount_max)) : ?>
                                    <?=number_format($grant_details->amount_min); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if(!empty($grant_details->amount_min) && $grant_details->amount_min != '0.00' && !empty($grant_details->amount_max) && $grant_details->amount_max == '0.00') : ?>
                                    Up to&nbsp;<?=number_format($grant_details->amount_min); ?> <?=$grant_details->amount_currency; ?>
                                <?php endif; ?>

                                <?php if (empty($grant_details->amount_min) && empty($grant_details->amount_max)) : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Description</h3>
                                <?php if(!empty($grant_details->description)) : ?>
                                    <?=nl2br(stripslashes($grant_details->description)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Requirements</h3>
                                <?php if(!empty($grant_details->requirements)) : ?>
                                    <?=nl2br(stripslashes($grant_details->requirements)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Geographic Focus</h3>
                                <?php if (!empty($grant_details->geo_location)): ?>
                                    <?php foreach($grant_details->geo_location as $k=>$v):?>
                                        <?= stripslashes($v->geo_location); ?><br>
                                    <?php endforeach;?>
                                <?php else: ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Restrictions</h3>
                                <?php if(!empty($grant_details->restrictions)) : ?>
                                    <?=nl2br(stripslashes($grant_details->restrictions)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                            <div class="grant-details">
                                <h3>Samples</h3>
                                <?php if(!empty($grant_details->samples)) : ?>
                                    <?=nl2br(stripslashes($grant_details->samples)); ?>
                                <?php else : ?>
                                    (not specified)
                                <?php endif; ?>
                            </div>

                        </div>

                        <div class="clear"></div>

                    </div><!-- end grant program info -->

                    <!-- Contact info -->
                    <div class="tabbed_wrapper">
                        <h2 id="tabbed_header">Contact Info</h2>
                        <div class="tabbed_header">

                            <?php if(!empty($grant_details->contact_info)) : ?>
                                <?php foreach($grant_details->contact_info as $k=>$v) : ?>
                                    <p>
                                        <?php if(!empty($v->contact_name)):?><?= stripslashes($v->contact_name); ?><?php endif;?><?php if(!empty($v->contact_title) && !empty($v->contact_name)):?>, <?php endif;?>
                                        <?php if(!empty($v->contact_title)) : ?><?= stripslashes($v->contact_title); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_title) || !empty($v->contact_name)) : ?><br /><?php endif; ?>

                                        <?php if(!empty($v->contact_org_dept)) : ?>
                                            <?= stripslashes($v->contact_org_dept); ?><br />
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_address1) and !empty($v->contact_address2)) : ?>
                                            <?= stripslashes($v->contact_address1); ?>,
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_address1) and empty($v->contact_address2)) : ?>
                                            <?= stripslashes($v->contact_address1); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_address2)) : ?>
                                            <?= stripslashes($v->contact_address2); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_address1) || !empty($v->contact_address2)) : ?><br /><?php endif;?>

                                        <?php if(!empty($v->contact_city)) : ?>
                                            <?= stripslashes($v->contact_city); ?>,
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_state)) : ?>
                                            <?= stripslashes($v->contact_state); ?>
                                        <?php endif; ?>

                                        <?php if(!empty($v->contact_zip)) : ?>
                                            <?= stripslashes($v->contact_zip); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($v->contact_city) || !empty($v->contact_state) || !empty($v->contact_zip)) : ?><br /><?php endif; ?>

                                        <?php if(!empty($v->country)) : ?>
                                            <?= stripslashes($v->country); ?><br />
                                        <?php endif; ?>

                                        <?php
                                        $phones = '';
                                        if (!empty($v->contact_phone_1)) {
                                            $phones = stripslashes($v->contact_phone_1);
                                        }
                                        if (!empty($v->contact_phone_2)) {
                                            if ($phones != '') {
                                                $phones .= ', ' . stripslashes($v->contact_phone_2);
                                            }
                                            else {
                                                $phones = stripslashes($v->contact_phone_2);
                                            }
                                        }
                                        echo $phones . '<br />';
                                        ?>


                                        <?php if(!empty($v->contact_fax)) : ?>
                                            fax: <?= stripslashes($v->contact_fax); ?><br />
                                        <?php endif; ?>
                                        <?php $email = ''; ?>
                                        <?php if (!empty($v->contact_email_1)) : ?>
                                            <?php $email = '<a href="mailto:'. $v->contact_email_1 . '">' . $v->contact_email_1 . '</a>' ?>
                                        <?php endif; ?>
                                        <?php if (!empty($v->contact_email_2) and $email != '') : ?>
                                            <?php $email .= ', ' . '<a href="mailto:'. $v->contact_email_2 . '">' . $v->contact_email_2 . '</a>' ?>
                                        <?php elseif (!empty($v->contact_email_2) and $email == '') : ?>
                                            <?php $email = '<a href="mailto:'. $v->contact_email_2 . '">' . $v->contact_email_2 . '</a>' ?>
                                        <?php endif; ?>
                                        <?php if ($email != '') : ?>
                                            email: <?= $email ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endforeach;?>
                            <?php endif;?>

                        </div>

                    </div><!-- end sponsor info -->

                <?php endif; ?>
            </div><!-- content_left-->

            <div id="content_right">

                <?php if ( !(empty($grant_details)) ) : ?>	<!-- if no grant with id specified -->
                    <!-- technical codes -->

                    <!-- Email alerts -->
                    <h2>Email Alerts</h2>
                    <?php
                    if ( $grant_details->email_alerts == "1"){
                        $chkEmail = "checked='checked'";
                    }else{
                        $chkEmail = "";
                    }
                    ?>
                    <p><input type="checkbox" <?=$chkEmail;?> disabled /> Included in email alerts?</p>

                    <!-- Deadlines -->
                    <h2>Deadline(s)</h2>
                    <ul>
                        <?php if(!empty($grant_details->deadline_data)) : ?>
                            <?php foreach( $grant_details->deadline_data AS $key=>$value ) : ?>
                                <li><?=date('F',mktime(0, 0, 0, $value->month, 1, 0)) . ' ' . $value->date?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </ul>

                    <h2>Deadline satisfied by:</h2>
                    <ul>
                        <?php if(!empty($grant_details->deadline_data)) : ?>
                            <?php foreach( $grant_details->deadline_data AS $key=>$value ) : ?>
                                <?php if($value->satisfied == 'R') : ?>
                                    <li>Received</li>
                                <?php elseif($value->satisfied == 'P') : ?>
                                    <li>Postmarked</li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </ul>

                    <h2>Key Date(s)</h2>
                    <ul>
                        <?php
                        if ( !empty($grant_details->key_dates || !empty($revisit_date) )) {
                            foreach( $grant_details->key_dates as $key=>$value ) {
                                if (!$value->year) {
                                    $value->year = date('Y');
                                }
                                if ($value->date_title == 'LOI') {
                                    $value->date_title = 'Letter of Intent';
                                }
                                else if ($value->date_title =='Board Mtg') {
                                    $value->date_title = 'Board Meeting';
                                }
                                else if ($value->date_title == 'Mini Proposal') {
                                    $value->date_title = 'Mini/Pre-Proposal';
                                }
                                else if ($value->date_title == 'Web or Live Conference') {
                                    $value->date_title = 'Informational Session/Workshop';
                                }
                                else if ($value->date_title =='Semifinals') {
                                    $value->date_title = 'Notification of Awards';
                                }
                                echo '<li>' . ucwords($value->date_title) . ' - ' . $value->date . ' ' . date('F',mktime(0, 0, 0, $value->month)) . '</li>';
                            }
                            if ( !empty( $revisit_date ) ) {
                                echo '<li>' . ucwords($revisit_date->date_title) . ' - ' . $revisit_date->date . ' ' . date('F',mktime(0, 0, 0, $revisit_date->month)) . ' ' . $revisit_date->year . '</li>';
                            }

                        }
                        else {
                            echo '(not specified)';
                        }
                        ?>
                    </ul>

                    <!-- Segment codes -->
                    <h2>Segment Codes</h2>
                    <ul>
                        <?php if(!empty($grant_details->segment_data)) : ?>
                            <?php foreach( $grant_details->segment_data AS $key=>$value ) : ?>
                                <li><?=ucwords( stripslashes($value->segment_title) );?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </ul>

                    <!-- Programs codes -->
                    <h2>Programs</h2>
                    <ul>
                        <?php if(!empty($grant_details->program_data)) : ?>
                            <?php foreach( $grant_details->program_data AS $key=>$value ) : ?>
                                <li><?=ucwords( stripslashes($value->program_title) );?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </ul>

                    <!-- Subject codes -->
                    <h2>Subjects</h2>
                    <ul>
                        <?php if(!empty($grant_details->subject_data)) : ?>
                            <?php foreach( $grant_details->subject_data AS $key=>$value ) : ?>
                                <li><?=ucwords( stripslashes($value->subject_title) );?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif;?>
                    </ul>

                    <!-- Target populations -->
                    <h2>Target Populations</h2>
                    <ul>
                        <?php if(!empty($grant_details->target_data)) : ?>
                            <?php foreach($grant_details->target_data AS $key=>$value) : ?>
                                <li><?=ucwords( stripslashes($value->target_title) );?></li>
                            <?php endforeach; ?>
                        <?php else : ?>
                            (not specified)
                        <?php endif; ?>
                    </ul>

                <?php endif; ?>
            </div><!-- content_right -->
            <div class="clear"></div>

        </div><!-- content_wrapper-->

        <?php
        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }



    /**
     * Function grantselect_display_grant_not_found
     * Outputs page content with "grant not found" message
     * @return $res
     */
    function grantselect_display_grant_not_found()
    {
        ob_start();
        ?>
        <div id="content_wrapper">
            <div id="content_left">
                <!-- Intro Text -->
                <div id="intro">
                    <div id="notice-label" class="error">
                        Grant record not found
                    </div>
                </div>
                <!--intro-->
            </div>
        </div>
        <?php
        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }


    /**
     * Function grantselect_display_grant_edit
     * Outputs grant record edit form for display on website
     * @params  $grant_details
     *          $user_notice
     *          $sponsor_list
     *          $sponsor_type_list
     *          $segment_list
     *          $geo_locations_domestic_list
     *          $geo_locations_foreign_list
     *          $programs_list
     *          $subjects_list
     *          $target_populations_list
     * @return $res
     */
    function grantselect_display_grant_edit( $grant_details, $user_notice, $sponsor_list, $sponsor_type_list, $segment_list, $geo_locations_domestic_list, $geo_locations_foreign_list, $programs_list, $subjects_list, $target_populations_list )
    {

        if ( !current_user_can('view_grants') || !current_user_can('edit_grants') ) {
            ob_start();
            ?>
            <div id="content_wrapper">
                <div id="content_left">
                    <!-- Intro Text -->
                    <div id="intro">
                        <div id="notice-label" class="error">
                            Sorry, you are not authorized to view grant data.
                        </div>
                    </div>
                    <!--intro-->
                </div>
            </div>
            <?php
            $res = ob_get_contents();
            ob_end_clean();

            return $res;
        }
//        echo "<pre>";
//        print_r($grant_details);
//        echo "</pre>";

//        echo "<pre>";
//        print_r($sponsor_list);
//        echo "</pre>";

        //todo: $page_number
        $page_number = 1; //test

        if ( empty($user_notice) ) {
            $user_notice=array('msg'=>'','class'=>'');
        }

        if ( empty($grant_details) || $grant_details->id == 0 ) {  // add new grant
            $grant_id = 'add';
            $grant_details->status = 'P';   //set default status
            $revisit_date = $grant_details->revisit[0];
            $form_mode = "add";
            $button_value = "Add This New Grant";
        } else {    // edit existing grant
            $grant_id = $grant_details->id;
            $revisit_date = self::get_revisit_date( $grant_id );
            $form_mode = "edit";
            $button_value = "Update Grant";
        }

        ob_start();
        ?>
        <div id="content_wrapper">
            <div id="content_left">

                <!-- Intro Text -->
                <?php
                if ( !empty($user_notice['msg']) ) {
                    ?>
                    <div id="intro">
                        <div id="notice-label"
                             class="<?php echo $user_notice['class']; ?>"><?php echo $user_notice['msg']; ?></div>
                    </div>
                    <?php
                }
                ?>
                <!--intro-->

                <div id="editorial_form">
                    <?php
                    if( isset($grant_details->sponsor) || $grant_id == 'add' ) {
                    ?>
                    <form method="post" name="sponsor" id="sponsor" action="/editor/records/<?= $form_mode ?>/<?php if (!empty($grant_id)) echo "?gid=" . $grant_id; ?>">
                        <fieldset>
                            <!-- Sponsor info -->
                            <legend>Sponsor Information</legend>
                            <a name="sponsor_info"></a>
                            <script>
                                //TO BE USED BY COUNTRY DROPDOWN AND STATE DROPDOWN
                                var postState = '<?php echo $grant_details->sponsor[0]->sponsor_state;?>';
                                //alert('<?=$grant_details->sponsor[0]->sponsor_state ?>');
                                <?php
                                    $selected_country = $grant_details->sponsor[0]->sponsor_country;
                                    if (empty($selected_country)) {
                                        $selected_country = 'United States';
                                    }
                                ?>
                                var postCountry = '<?php echo $selected_country; ?>';
                                //alert('<?=$grant_details->sponsor[0]->sponsor_country ?>');
                            </script>

                            <p><label for="all_sponsors">Select Sponsor</label>
                                <select name="all_sponsors" id="all_sponsors" onchange='selectSponsor()'>
                                    <option value="">Add Sponsor</option>
                                    <?php
                                    foreach ($sponsor_list as $key => $value) {
                                        $id = $key;
                                        $name = $value['sponsor_name'];
                                        $department = $value['sponsor_department'];
                                        if ( !empty($department) ) {
                                            $name .= " — " . $department;
                                        }
                                        $opt_val = $value['sponsor_info'];
                                        if ( $grant_details->sponsor[0]->id == $id) {
                                            echo "<option selected value=\"$opt_val\">$name</option>";
                                        }
                                        else {
                                            echo "<option value=\"$opt_val\">$name</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </p>

                            <input type="hidden" id="GrantSponsor_id" name="GrantSponsor[id]" value="<?php echo $grant_details->sponsor->id ?>"/>
                            <p><label for="sponsor_name">Name</label> <input type="text" id="sponsor_name" name="GrantSponsor[sponsor_name]" size="60" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_name) ?>"/></p>
                            <p><label for="sponsor_department">Department</label> <input type="text" id="sponsor_department" name="GrantSponsor[sponsor_department]" size="60" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_department) ?>"/></p>
                            <p><label for="sponsor_address1">Address 1</label>
                                <input type="text" id="sponsor_address1" size="60" name="GrantSponsor[sponsor_address]" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_address) ?>"/></p>
                            <p><label for="sponsor_address2">Address 2</label>
                                <input type="text" id="sponsor_address2" size="60" name="GrantSponsor[sponsor_address2]" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_address2) ?>"/></p>
                            <p><label for="sponsor_city">City</label> <input type="text" id="sponsor_city" name="GrantSponsor[sponsor_city]" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_city) ?>"/></p>
                            <p><label for="sponsor_country">Country</label> <select id='countrySelect' name="GrantSponsor[sponsor_country]" onchange='populateState()'></select>
                                <select id='stateSelect' name="GrantSponsor[sponsor_state]"></select>
                                <script type="text/javascript">initCountry('<?php echo $selected_country; ?>'); </script></p>
                            <p><label for="sponsor_zip">Postal Code</label> <input type="text" id="sponsor_zip" name="GrantSponsor[sponsor_zip]"  value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_zip) ?>"/></p>
                            <p><label for="sponsor_url">Website</label> <input type="text" id="sponsor_url" size="60" name="GrantSponsor[sponsor_url]" value="<?php echo stripslashes($grant_details->sponsor[0]->sponsor_url) ?>"/></p>
                            <p><label for="sponsor_type">Type</label>
                                <select id="GrantSponsor_grant_sponsor_type_id" name="GrantSponsor[grant_sponsor_type_id]">
                                    <option value=""></option>
                                    <?php
                                    foreach ($sponsor_type_list as $key => $value) {
                                        echo '<option ';
                                        if ($value->id == $grant_details->sponsor[0]->grant_sponsor_type_id) {
                                            echo 'selected="selected" ';
                                        }
                                        echo 'value="' . $value->id . '">' . $value->sponsor_type . '</option>';
                                    }
                                    ?>
                                </select>
                            </p>
                        </fieldset>

                        <!-- START: CONTACT INFORMATION -->
                        <fieldset>
                            <input type="hidden" id="remove_contacts_ids" name="remove_contact" value="empty" />
                            <legend>Contact Information</legend>
                            <?php
                            //Loop through Contact array
                            $counter = 0;
                            foreach ( $grant_details->contact_info AS $key => $value ){
                                $counter++;
                                ?>
                                <script>
                                    //TO BE USED BY COUNTRY DD AND STATE DD
                                    var postState2 = '<?php echo $value->contact_state ?>';
                                    //alert('<?=$value->contact_state ?>');
                                    var postCountry2 = '<?php
                                        $selected_country2 = $value->country;
                                        if (empty($selected_country2)) {
                                            $selected_country2 = 'United States';
                                        }
                                        echo $selected_country2;
                                        ?>';
                                    //alert('<?=$value->country ?>');
                                </script>

                                <fieldset id="contact_info<?= $counter ?>">
                                    <a name="contact_info"></a>
                                    <input type="hidden" id="contact_id<?= $counter ?>" name="contact[id][]" value="<?php echo $value->id ?>"/>
                                    <p><label for="contact_name">Name</label> <input type="text" id="contact_name" size="40" name="contact[contact_name][]" class="cn<?= $counter ?>" value="<?= stripslashes($value->contact_name) ?>"/> <span class="remove-contact" id="remove_contact<?= $counter ?>" onclick="del_contact('<?= $counter ?>'); return false">[ - ] reset fields</span></p>
                                    <p><label for="contact_title">Title</label>
                                        <input type="text" id="contact_title" size="40" name="contact[contact_title][]" class="ct<?= $counter ?>" value="<?= stripslashes($value->contact_title) ?>"/></p>
                                    <p><a href="#" class="fill-sponsor-link" id="fill_sponsor<?php $counter ?>" onclick="javascript:fill_contact_information1('<?= $counter ?>');return false;">[ fill in with sponsor address ]</a></p>
                                    <p><label for="contact_org_dept">Org./Dept.</label>
                                        <input type="text" id="contact_org_dept<?= $counter ?>" size="40" name="contact[contact_org_dept][]" value="<?= stripslashes($value->contact_org_dept) ?>"/></p>
                                    <p><label for="contact_address1">Address 1</label>
                                        <input type="text" id="contact_address1<?= $counter ?>" size="60" name="contact[contact_address1][]" value="<?= stripslashes($value->contact_address1) ?>"/></p>
                                    <p><label for="contact_address2">Address 2</label>
                                        <input type="text" id="contact_address2<?= $counter ?>" size="60" name="contact[contact_address2][]" value="<?php echo stripslashes($value->contact_address2) ?>"/></p>
                                    <p><label for="contact_city">City</label>
                                        <input type="text" id="contact_city<?= $counter ?>" name="contact[contact_city][]" value="<?php echo stripslashes($value->contact_city) ?>"/></p>
                                    <p><label for="contact_country">Country</label> <select id="countrySelect<?= $counter ?>" name="contact[country][]" onchange='populateState2(<?= $counter ?>)'></select>
                                        <span id="state_helper_id"><select id="stateSelect<?= $counter ?>" name="contact[contact_state][]"></select></span>
                                        <script type="text/javascript">initCountry2('<? echo $selected_country2 ?>', <?= $counter ?>); </script></p>
                                    <p><label for="contact_zip">Postal Code</label>
                                        <input type="text" id="contact_zip<?= $counter ?>"  name="contact[contact_zip][]" value="<?php echo stripslashes($value->contact_zip) ?>"/></p>
                                    <p><label for="contact_phone">Phone 1</label>
                                        <input type="text" id="contact_phone<?= $counter ?>"  name="contact[contact_phone_1][]" value="<?php echo stripslashes($value->contact_phone_1) ?>"/></p>
                                    <p><label for="contact_phone">Phone 2</label>
                                        <input type="text" id="contact_phone2<?= $counter ?>"  name="contact[contact_phone_2][]" value="<?php echo stripslashes($value->contact_phone_2) ?>"/></p>
                                    <p><label for="contact_fax">Fax</label>
                                        <input type="text" id="contact_fax<?= $counter ?>"  name="contact[contact_fax][]" value="<?php echo stripslashes($value->contact_fax) ?>"/></p>
                                    <p><label for="contact_email">Email 1</label>
                                        <input type="text" id="contact_email<?= $counter ?>"  name="contact[contact_email_1][]" value="<?php echo stripslashes($value->contact_email_1) ?>"/></p>
                                    <p><label for="contact_email">Email 2</label>
                                        <input type="text" id="contact_email2<?= $counter ?>"  name="contact[contact_email_2][]" value="<?php echo stripslashes($value->contact_email_2) ?>"/>
                                    </p>
                                </fieldset>

                                <?php
                            }//End: Foreach
                            ?>

                            <div id="doc">
                            <div id="content"></div>
                            <p id="add-element">[ + ] add more</p>
                            </div>

                        </fieldset>

                        <!-- GRANT START -->

                        <fieldset>
                            <!-- Grant program info -->
                            <legend>Grant Information</legend>
                            <a name="grant_info"></a>
                            <p><label for="grant_title">Grant Title</label>
                                <input type="hidden" name="grant[id]" value="<?php echo $grant_id ?>"/>
                                <input type="text" id="grant_title" size="60" name="grant[title]" value="<?php echo stripslashes($grant_details->title) ?>"/></p>
                            <p><label for="grant_url">Grant URL</label>
                                <input type="text" id="grant_url" size="60" name="grant[grant_url_1]" value="<?php echo stripslashes($grant_details->grant_url_1) ?>"/></p>
                            <p><label for="grant_url">CFDA</label>
                                <input type="text" id="grant_url" size="60" name="grant[cfda]" value="<?php echo stripslashes($grant_details->cfda) ?>"/></p>
                            <p><label for="grant_description">Description<a name="grant_description"></a></label>
                                <textarea rows="15" cols="55" id="grant_description" name="grant[description]"><?php echo stripslashes($grant_details->description) ?></textarea></p>
                            <p><label for="grant_requirements">Requirements<a name="grant_requirements"></a></label>
                                <textarea rows="15" cols="55" id="grant_requirements" name="grant[requirements]"><?php echo stripslashes($grant_details->requirements) ?></textarea></p>
                            <p><label for="grant_restrictions">Restrictions<a name="grant_restrictions"></a></label>
                                <textarea rows="15" cols="55" id="grant_restrictions" name="grant[restrictions]"><?php echo stripslashes($grant_details->restrictions) ?></textarea></p>

                            <p>
                                <label for="geo_restrictions_domestic">Geo Restrictions<a name="geo_restrictions"></a></label>

                                <select id="GrantGeoLocation_geo_location" multiple="yes" name="GrantGeoLocation[geo_location][]" size="20">
                                    <option value="1">-----All States-----</option>
                                    <?php
                                        foreach ( $geo_locations_domestic_list as $key=>$value ) {
                                            echo '<option value="' . $value->id . '">' . stripslashes($value->geo_location) . '</option>';
                                        }
                                    ?>
                                    <option value="247">---All Countries---</option>
                                    <?php
                                    foreach ( $geo_locations_foreign_list as $key=>$value ) {
                                        echo '<option value="' . $value->id . '">' . stripslashes($value->geo_location) . '</option>';
                                    }
                                    ?>
                                </select>

                                <input type="button" id="remove_geo" value="<<">
                                <input type="button" id="add_geo" value=">>">

                                <select id="GrantGeoLocation_geo_location2" multiple="yes" name="GrantGeoLocation[geo_location2][]" size="20">
                                    <?php
                                    foreach ( $grant_details->geo_location as $key=>$value ) {
                                        if ( $key == 1 ) {
                                            echo '<option value="' . $value->id . '">' . '-----All States-----' . '</option>';
                                        } else if ( $key == 247 ) {
                                            echo '<option value="' . $value->id . '">' . '---All Countries---' . '</option>';
                                        } else {
                                            echo '<option value="' . $value->id . '">' . stripslashes($value->geo_location) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </p>
                            <p>
                                <label for="grant_samples">Samples<a name="grant_samples"></a></label> <textarea rows="5" cols="55" id="grant_samples" name="grant[samples]"><?php echo $grant_details->samples ?></textarea>
                            </p>
                        </fieldset>

                        <fieldset>

                            <!-- START: KEYDATES -->
                            <fieldset class="deadlines-key-dates-group">
                                <input type="hidden" id="remove_deadlines_ids" name="remove_dead_line" value="empty" />
                                <legend>Deadlines and Key Dates</legend>
                                <a name="deadlines"></a>

                                <?php
                                $GrantDeadlineData = $grant_details->deadline_data;
                                if ( empty($GrantDeadlineData) ) {
                                    $GrantDeadlineData  = array('0'=>'');
                                }
                                $counter = 0;
                                foreach ( $GrantDeadlineData AS $key=>$value  ) :
                                    $counter++;
                                    ?>

                                    <p id="dead_date<?= $counter ?>">
                                        <label for="deadline1">Deadline</label>
                                        <input type="hidden" id="deadline_id<?= $counter ?>" name="deadline[id][]" value="<?=$value->id?>" />
                                        <select class="deadline-month" name="deadline[month][]" id="dead_month<?= $counter ?>">
                                            <option value=""></option>
                                            <?php for ($i = 1; $i <= 12; $i++) : $dd= ''; ?>
                                                <?php $mon = date("M", mktime(0, 0, 0, $i+1, 0, 0)); ?>
                                                <?php if ($value->month == $i) { ?>
                                                    <option selected="selected" value="<?=$i;?>" ><?=$mon;?></option>
                                                <?php } else { ?>
                                                    <option value="<?=$i;?>" ><?=$mon;?></option>
                                                <?php } ?>
                                            <?php endfor; ?>
                                        </select>

                                        <select class="deadline-day" name="deadline[day][]" id="dead_day<?= $counter ?>">
                                            <option value=""></option>
                                            <?php $selectedD = "";
                                            for ( $i=1; $i<= 31; $i++ ) : ?>
                                                <?php if ($i == $value->date) : $selectedD = "selected"; endif; ?>
                                                <option value="<?=$i?>" <?=$selectedD?> ><?=$i?></option>
                                                <?php
                                                $selectedD = "";
                                            endfor;
                                            ?>
                                        </select>
                                        <span class="remove-deadline" id="remove_deadline<?= $counter?>" onclick="del_deadline('<?= $counter ?>'); return false">[ - ] remove</span>
                                    </p>

                                    <p id="dead_satisfied<?= $counter ?>">
                                        <label for="deadline_satisfied">Satisfied</label>
                                        <select class="deadline-satisfied" name="deadline[satisfied][]" id="dead_sat<?= $counter ?>">
                                            <option value=""></option>
                                            <option value="P" <?php if($value->satisfied =='P'  ){ echo 'selected="selected"'; } ?> >Postmark</option>
                                            <option value="R" <?php if($value->satisfied =='R'  ){ echo 'selected="selected"'; } ?>>Receipt of Application</option>
                                        </select>
                                    </p>
                                    <p id="dead_border<?= $counter ?>"><br></p>
                                    <?php
                                endforeach; //END: FOREACH
                                ?>

                                <div id="deadlineContent">
                                <div id="content"></div>
                                <p id="add-deadline">[ + ] add another</p>
                                </div>

                                <input type="hidden" id="remove_keydates_ids" name="key_dates_line" value="empty" />
                                <?php
                                $GrantKeyDateData = $grant_details->key_dates;

                                if ( empty($GrantKeyDateData) ) {
                                    $GrantKeyDateData  = array('0'=>'');
                                }

                                $counter = 0;
                                foreach ( $GrantKeyDateData AS $key=>$value  ){
                                    $counter++;
                                    ?>
                                    <input type="hidden" id="key_dates<?= $counter ?>" name="keydates[id][]" value="<?php echo $value->id ?>"/>
                                    <p id="key_date<?= $counter ?>">
                                        <label for="keydate1">Key Dates</label>
                                        <select class="key-date-title" id="key_date_title<?= $counter ?>" name="keydates[date_title][]">
                                            <option value=""></option>
                                            <option value="LOI"  <?php if($value->date_title =='LOI'  ){ echo 'selected="selected"'; } ?> >Letter of Intent</option>
                                            <option value="Board Mtg" <?php if($value->date_title =='Board Mtg'  ){ echo 'selected="selected"'; } ?> >Board Meeting</option>
                                            <option value="Mini Proposal" <?php if($value->date_title =='Mini Proposal'  ){ echo 'selected="selected"'; } ?> >Mini/Pre-Proposal</option>
                                            <option value="Web or Live Conference" <?php if($value->date_title =='Web or Live Conference'  ){ echo 'selected="selected"'; } ?> >Informational Session/Workshop</option>
                                            <option value="Semifinals" <?php if($value->date_title =='Semifinals'  ){ echo 'selected="selected"'; } ?> >Notification of Awards</option>
                                        </select>

                                        <?php
                                        $selected = $value->month;
                                        $dd = '<select class="key-date-month" id="key_month' . $counter . '" name="keydates[month][]">';
                                        $dd .= '<option value=""></option>';
                                        for ($i = 1; $i <= 12; $i++)
                                        {
                                            $selMonth =  date("n", mktime(0, 0, 0, $i+1, 0, 0));
                                            $dd .= '<option value="' . $i . '"';
                                            if ($selMonth == $selected)
                                            {
                                                $dd .= ' selected="selected"';
                                            }
                                            /*** get the month ***/
                                            $mon = date("M", mktime(0, 0, 0, $i+1, 0, 0));
                                            $dd .= '>'.$mon.'</option>';
                                        }
                                        $dd .= '</select>';
                                        echo $dd;
                                        ?>
                                        <select class="key-date-day" id="key_day<?= $counter ?>" name="keydates[day][]">
                                            <option value=""></option>
                                            <?php
                                            for ( $i=1; $i<= 31; $i++ ){
                                                if ($i == $value->date)
                                                {
                                                    $selectedD = ' selected="selected"';
                                                }
                                                echo '	<option value="'.$i.'" '.$selectedD.' >'.$i.'</option>';
                                                $selectedD = "";
                                            }
                                            ?>
                                        </select><span class="remove-deadline" id="remove_deadline<?= $counter?>" onclick="del_key_dates('<?= $counter ?>'); return false">[ - ] remove</span>

                                    <p class="key-dates-border" id="key_dates_border<?= $counter ?>"><br></p>

                                    <?php
                                }//END: FOREACH
                                ?>

                                <div id="keydatesContent">
                                <div id="content"></div>
                                <p id="add-keydates">[ + ] add another</p>
                                </div>

                            </fieldset>

                            <fieldset>
                                <legend>Amounts</legend>
                                <a name="amounts"></a>
                                <p>
                                    <label for="amount_range_minimum">Amount Min.</label>
                                    <?php if ($grant_details->amount_min == '') { ?>
                                        <input type="text" id="amount_range_minimum" name="grant[amount_min]" value="" />
                                    <?php }
                                    else { ?>
                                        <input type="text" id="amount_range_minimum" name="grant[amount_min]" value="<?php echo number_format($grant_details->amount_min); ?>"/>
                                    <?php } ?>
                                    </p>
                                <p><label for="amount_range_maximum">Amount Max.</label>
                                    <?php if ($grant_details->amount_max == '' or $grant_details->amount_max == 0.00) { ?>
                                    <input type="text" id="amount_range_maximum" name="grant[amount_max]" value="" /></p>
                                <?php }
                                else { ?>
                                    <input type="text" id="amount_range_maximum" name="grant[amount_max]" value="<?php echo number_format($grant_details->amount_max); ?>"/></p>
                                <?php } ?>
                                <p><label for="amount_notes">Amt. Notes</label>
                                    <textarea rows="3" cols="55" id="amount_notes" name="grant[amount_notes]"><?php echo $grant_details->amount_notes ?></textarea>

                                </p>
                                <p>
                                    <label>Amount Currency</label>

                                    <?php
                                    $amount_currency = $grant_details->amount_currency;
                                    if ( !empty( $amount_currency) ) {
                                        $currency_selected[$amount_currency] = 'selected="selected"';
                                    } else {
                                        $currency_selected[$amount_currency] = '';
                                    }

                                    ?>

                                    <select id="grant_amount_currency" name="grant[amount_currency]">
                                        <?php
                                        foreach ( self::GRANT_CURRENCY as $long_title=>$abbreviation ) {
                                            echo '<option value="' . $abbreviation . '"';
                                            if ( $abbreviation == $grant_details->amount_currency ) {
                                                echo ' selected="selected"';
                                            }
                                            echo '>' . $long_title . '</option>';
                                        }
                                        ?>
                                    </select>
                                </p>
                            </fieldset>

                            <fieldset>
                                <legend>Segment (Book) Codes</legend>
                                <a name="book_codes"></a>
                                <p>(check all that apply)</p>

                                <?php
                                $y = 1;
                                $arrKeys = array_keys( $grant_details->segment_data );

//                                echo "<pre>";
//                                print_r($segment_list);
//                                echo "</pre>";

                                foreach ( $segment_list AS $key => $value ){
                                    if ( $y == 1 ){
                                        echo '<div class="faux_section"><p>';
                                    }

                                    //Search for selected checkbox
                                    if (in_array($value->id, $arrKeys)) {
                                        $chk = " checked";
                                    }

                                    if ($value->segment_title == 'Education' || $value->segment_title == 'Scholarships and Fellowships') {
                                        echo '<input type="checkbox" name="GrantSegmentMappings[segment_id][]" value="' . $value->id . '" ' . $chk . '/><strike>' . stripslashes($value->segment_title) . '</strike><br />';
                                    }
                                    else {
                                        echo '<input type="checkbox" name="GrantSegmentMappings[segment_id][]" value="' . $value->id . '" ' . $chk . '/>' . stripslashes($value->segment_title) . '<br />';
                                    }
                                    if ( $y == 5 ){
                                        echo '</p></div>';
                                        $y = 0;
                                    }
                                    $y++;
                                    $chk = "";
                                }
                                ?>
                            </fieldset>

                            <fieldset>
                                <legend>Program Type</legend>
                                <a name="programs"></a>
                                <p>For multiple selections, hold down <strong>CTRL</strong> (<strong>Command</strong> for Macs) while clicking selections.</p>
                                <p>
                                    <select id="GrantProgramMappings_program_id" multiple="yes" name="GrantProgramMappings[program_id][]" size="10">
                                        <?php
                                        $arrKeys = array_keys( $grant_details->program_data );
                                        foreach ( $programs_list as $key=>$value ){

                                            //Search for selected checbox
                                            if (in_array($value->id, $arrKeys)) {
                                                $selected = 'selected="selected"';
                                            } else {
                                                $selected = '';
                                            }

                                            echo '	<option ' . $selected . ' value="' . $value->id . '">' . stripslashes($value->program_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </p>
                            </fieldset>

                            <fieldset>
                                <legend>Subject Headings</legend>
                                <a name="subjects"></a>
                                <p>For multiple selections, hold down <strong>CTRL</strong> (<strong>Command</strong> for Macs) while clicking selections.</p>

                                <select id="GrantSubjectMappings_subject_title" multiple="yes" name="GrantSubjectMappings[subject_title][]" size="20">
                                    <?php
                                    foreach ( $subjects_list as $key=>$value ) {
                                        echo '<option value="' . $value->id . '">' . stripslashes($value->subject_title) . '</option>';
                                    }
                                    ?>
                                </select>

                                <input type="button" id="remove" value="<<">
                                <input type="button" id="add" value=">>">

                                <select id="GrantSubjectMappings_subject_title2" multiple="yes" name="GrantSubjectMappings[subject_title2][]" size="20">
                                    <?php
                                    foreach ( $grant_details->subject_data as $key=>$value ) {
                                        echo '<option value="' . $value->id . '">' . stripslashes($value->subject_title) . '</option>';
                                    }
                                    ?>
                                </select>

                                <script type="text/javascript">
                                    <!--
                                    //alert (document.getElementById('GrantSubjectMappings_subject_title'));
                                    var myfilter = new filterlist(document.getElementById('GrantSubjectMappings_subject_title'));
                                    //-->
                                </script>

                                <div class="filter-field">
                                    <p>Filter:
                                        <a title="Clear the filter"href="javascript:myfilter.reset()">Clear</a>
                                        <a title="Show items starting with A" href="javascript:myfilter.set('^A')">A</a>
                                        <a title="Show items starting with B" href="javascript:myfilter.set('^B')">B</a>
                                        <a title="Show items starting with C" href="javascript:myfilter.set('^C')">C</a>
                                        <a title="Show items starting with D" href="javascript:myfilter.set('^D')">D</a>
                                        <a title="Show items starting with E" href="javascript:myfilter.set('^E')">E</a>
                                        <a title="Show items starting with F" href="javascript:myfilter.set('^F')">F</a>
                                        <a title="Show items starting with G" href="javascript:myfilter.set('^G')">G</a>
                                        <a title="Show items starting with H" href="javascript:myfilter.set('^H')">H</a>
                                        <a title="Show items starting with I" href="javascript:myfilter.set('^I')">I</a>
                                        <a title="Show items starting with J" href="javascript:myfilter.set('^J')">J</a>
                                        <a title="Show items starting with K" href="javascript:myfilter.set('^K')">K</a>
                                        <a title="Show items starting with L" href="javascript:myfilter.set('^L')">L</a>
                                        <a title="Show items starting with M" href="javascript:myfilter.set('^M')">M</a>
                                        <a title="Show items starting with N" href="javascript:myfilter.set('^N')">N</a>
                                        <a title="Show items starting with O" href="javascript:myfilter.set('^O')">O</a>
                                        <a title="Show items starting with P" href="javascript:myfilter.set('^P')">P</a>
                                        <a title="Show items starting with Q" href="javascript:myfilter.set('^Q')">Q</a>
                                        <a title="Show items starting with R" href="javascript:myfilter.set('^R')">R</a>
                                        <a title="Show items starting with S" href="javascript:myfilter.set('^S')">S</a>
                                        <a title="Show items starting with T" href="javascript:myfilter.set('^T')">T</a>
                                        <a title="Show items starting with U" href="javascript:myfilter.set('^U')">U</a>
                                        <a title="Show items starting with V" href="javascript:myfilter.set('^V')">V</a>
                                        <a title="Show items starting with W" href="javascript:myfilter.set('^W')">W</a>
                                        <a title="Show items starting with X" href="javascript:myfilter.set('^X')">X</a>
                                        <a title="Show items starting with Y" href="javascript:myfilter.set('^Y')">Y</a>
                                        <a title="Show items starting with Z" href="javascript:myfilter.set('^Z')">Z</a>
                                    </p>
                                    <p>Filter by regular expression:
                                        <input onkeyup="myfilter.set(this.value)" name="regexp"> <input onclick="myfilter.set(this.form.regexp.value)" type="button" value="Filter"> <input onclick="myfilter.reset();this.form.regexp.value=''" type="button" value="Clear">
                                        <input onclick="myfilter.set_ignore_case(!this.checked)" type="checkbox" name="toLowerCase"> Case-sensitive
                                    </p>
                                </div>
                            </fieldset><!-- end subject headings fieldset -->

                            <fieldset>
                                <legend>Target Populations</legend>
                                <a name="populations"></a>
                                <p>For multiple selections, hold down <strong>CTRL</strong> (<strong>Command</strong> for Macs) while clicking selections.</p>
                                <p>
                                    <select id="GrantTargetMappings_target_title" multiple="yes" name="GrantTargetMappings[target_title][]" size="5">
                                        <?php
                                        $arrKeys = array_keys( $grant_details->target_data );
                                        foreach ( $target_populations_list as $key=>$value ){

                                            //Search for selected checkbox
                                            if (in_array($value->id, $arrKeys)) {
                                                $selected = 'selected="selected"';
                                            } else {
                                                $selected = '';
                                            }

                                            echo '	<option ' . $selected . ' value="' . $value->id . '">' . stripslashes($value->target_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </p>
                            </fieldset>

                            <div class="faux_section">
                                <fieldset>
                                    <legend>Status</legend>
                                    <a name="grant_status"></a>
                                    <p><select class="grant-status" name="grant[status]">
                                            <option value="A" <?php if ( $grant_details->status =='A' ){ echo 'selected="selected"'; } ?> >Active</option>
                                            <option value="P" <?php if ( $grant_details->status =='P' ){ echo 'selected="selected"'; } ?> >Pending</option>
                                            <option value="R" <?php if ( $grant_details->status =='R' ){ echo 'selected="selected"'; } ?> >Ready for Review</option>
                                            <option value="S" <?php if ( $grant_details->status =='S' ){ echo 'selected="selected"'; } ?> >Suspended</option>
                                            <?php
                                            if ( $grant_details->status =='E' ) {
                                            ?>
                                                <option value="E" selected="selected">Error</option>
                                            <?php
                                            }
                                            ?>
                                        </select></p>
                                </fieldset>
                            </div>

                            <div class="faux_section">
                                <fieldset>
                                    <legend>Email Alerts</legend>
                                    <a name="alerts"></a>
                                    <p><input type="checkbox" name="grant[email_alerts]" <?php if($grant_details->email_alerts == '1'  ){ echo 'checked="checked"'; } else{ echo '';} ?>/> Included in email alerts?</p>
                                </fieldset>
                            </div>

                            <div>
                                <fieldset>
                                    <legend>Revisit Date</legend>
                                    <a name="revisit_date"></a>
                                    <input type="hidden" name="revisit[id]" value="<?php echo $revisit_date->id ?>"/>
                                    <?php
                                    $selected = $revisit_date->month;
                                    $dd = '<select name="revisit[month]" id="revisit_month">';
                                    $dd .= '<option value=""></option>';
                                    for ($i = 1; $i <= 12; $i++)
                                    {
                                        $selMonth =  date("n", mktime(0, 0, 0, $i+1, 0, 0));
                                        $dd .= '<option value="'.$i.'"';
                                        if ($selMonth == $selected)
                                        {
                                            $dd .= ' selected="selected"';
                                        }
                                        /*** get the month ***/
                                        $mon = date("M", mktime(0, 0, 0, $i+1, 0, 0));
                                        $dd .= '>'.$mon.'</option>';
                                    }
                                    $dd .= '</select>';
                                    echo $dd;
                                    ?>

                                    <select name="revisit[day]" id="revisit_day">
                                        <option value=""></option>
                                        <?php
                                        $selectedD = $revisit_date->date;

                                        for ( $i=1; $i<= 31; $i++ ){
                                            if ($i == $revisit_date->date)
                                            {
                                                $selectedD = ' selected="selected"';
                                            }

                                            echo '	<option value="'.$i.'" '.$selectedD.' >'.$i.'</option>';
                                            $selectedD = "";
                                        }
                                        ?>
                                    </select>

                                    <select name="revisit[year]" id="revisit_year">
                                        <option value=""></option>
                                        <?php
                                        $one   = date("Y",time());
                                        $two   = date("Y",time())+1;
                                        $three = date("Y",time())+2;
                                        $four  = date("Y",time())+3;
                                        $years = array($one, $two, $three, $four);
                                        foreach ($years as $year) {
                                            if ($year == $revisit_date->year) {
                                                echo '<option selected="selected" value=" ' . $year . ' ">' . $year . '</option>';
                                            }
                                            else {
                                                echo '<option value=" ' . $year . ' ">' . $year . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <input type="button" id="reset_rev_date" value="Reset" onclick="remove_revisit_date(); return false">
                                    </p>
                                </fieldset>
                            </div>

                            <p class="submit"><input type="hidden" name="action" value="<?= $form_mode ?>" /><input type="submit" value="<?= $button_value ?>" name="submit" /></p>
                            </form>

                            <?php
                            }//END: IF
                            ?>
                            <!-- END: FORM -->

                </div><!-- closes editorial_form div -->
            </div><!-- end content_left -->
        </div>

        <?php
        $res = ob_get_contents();
        ob_end_clean();

        return $res;
    }


    /**
     * Function add_grant_record
     * Adds submitted grant record to database
     * @params  $submitted_data
     * @return  array (success=>[true if successful, false if failed], msg=>[message for user])
     */
    function add_grant_record( $submitted_data )
    {
//        echo "<pre>";
//        print_r($submitted_data);
//        echo "</pre>";

        if ( !current_user_can('create_grants') ) {
            return array("success"=>false, "msg"=>"Sorry, you are not authorized to add grants.");
        }

        global $wpdb;

        $current_time = date("Y-m-d H:i:s");

        //insert main grant record
        if( !empty($submitted_data['grant']['title']) ) {

            //format "amount" data
            if (!empty($submitted_data['grant']['amount_min'])) {
                $temp = $submitted_data['grant']['amount_min'];
                $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
                $out  = '';
                foreach ($temp as $t) {
                    if (preg_match("/\d+|\,|\./", $t)) {
                        $out .= $t;
                    }
                }
                $submitted_data['grant']['amount_min'] = preg_replace("/\,/", '', $out);
            }
            elseif ($submitted_data['grant']['amount_min'] == '0') {
                $submitted_data['grant']['amount_min'] = '0.00';
            }
            else {
                $submitted_data['grant']['amount_min'] = NULL;
            }
            if (!empty($submitted_data['grant']['amount_max'])) {
                $temp = $submitted_data['grant']['amount_max'];
                $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
                $out = '';
                foreach ($temp as $t) {
                    if (preg_match("/\d+|\,|\./", $t)) {
                        $out .= $t;
                    }
                }
                $submitted_data['grant']['amount_max'] = preg_replace("/\,/", '', $out);
            }
            else {
                $submitted_data['grant']['amount_max'] = NULL;
            }

            //set email alert db value based on submitted value
            if ($submitted_data['grant']['email_alerts']){
                $submitted_data['grant']['email_alerts'] = '1';
            } else{
                $submitted_data['grant']['email_alerts'] = '0';
            }

            $table = $wpdb->prefix . 'gs_grants';
            $data = array(
                'title'             => $submitted_data['grant']['title'],
                'description'       => $submitted_data['grant']['description'],
                'requirements'      => $submitted_data['grant']['requirements'],
                'restrictions'      => $submitted_data['grant']['restrictions'],
                'samples'           => $submitted_data['grant']['samples'],
                'cfda'              => $submitted_data['grant']['cfda'],
                'amount_currency'   => $submitted_data['grant']['amount_currency'],
                'amount_min'        => $submitted_data['grant']['amount_min'],
                'amount_max'        => $submitted_data['grant']['amount_max'],
                'amount_notes'      => $submitted_data['grant']['amount_notes'],
                'grant_url_1'       => $submitted_data['grant']['grant_url_1'],
                'grant_url_2'       => $submitted_data['grant']['grant_url_2'],
                'status'            => $submitted_data['grant']['status'],
                'email_alerts'      => $submitted_data['grant']['email_alerts'],
                'updated_at'        => $current_time,
                'created_at'        => $current_time
            );
            $format = array( '%s','%s','%s','%s','%s','%s','%s','%f','%f','%s','%s','%s','%s','%d','%s','%s' );
            if ( $wpdb->insert($table, $data, $format) ) {
                $grant_ID = $wpdb->insert_id;
            } else {
                return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 1569INS]");
            };
        } else {
            return array("success"=>false, "msg"=>"Record has NOT been added. Please enter valid data into fields.");
        }

        if ( !empty($grant_ID) ) {

            //write to log
            $log_id = self::log_write( get_current_user_id(), $grant_ID );

            if ( empty($submitted_data['GrantGeoLocation']['geo_location2'][0]) ) {
                $submitted_data['GrantGeoLocation']['geo_location2'][0] = 1;    // assign "Domestic - All States" as default
            }

            //geo restrictions table submission
            $table = $wpdb->prefix . 'gs_grant_geo_mappings';
            $format = array( '%d','%d','%s','%s' );
            foreach ( $submitted_data['GrantGeoLocation']['geo_location2'] as $geo_id ) {
                if ( !empty($geo_id) ) {
                    $data = array(
                        'grant_id' => $grant_ID,
                        'geo_id' => $geo_id,
                        'updated_at' => $current_time,
                        'created_at' => $current_time
                    );
                    if ($wpdb->insert($table, $data, $format)) {
                        $geo_mapping_ID = $wpdb->insert_id;
                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1637GMI]", "grant_id"=>$grant_ID);
                    };
                }
            }

            //if sponsor information has been submitted
            if ( !empty($submitted_data['GrantSponsor']['sponsor_name']) ) {

                $table = $wpdb->prefix . 'gs_grant_sponsors';

                if (isset($submitted_data['all_sponsors']) and $submitted_data['all_sponsors'] != '') { //existing sponsor selected
                    $params_array = explode('@', $submitted_data['all_sponsors']);
                    $sponsor_ID = (int)$params_array[0];

                    //get existing sponsor data
                    $sponsor_data_existing = GrantSelectSearchAddOn::get_sponsor_data( $sponsor_ID );

                    //compare existing sponsor data to submitted data
                    if ( $sponsor_data_existing->sponsor_name          != $submitted_data['GrantSponsor']['sponsor_name'] ||
                         $sponsor_data_existing->sponsor_department    != $submitted_data['GrantSponsor']['sponsor_department'] ||
                         $sponsor_data_existing->sponsor_address       != $submitted_data['GrantSponsor']['sponsor_address'] ||
                         $sponsor_data_existing->sponsor_address2      != $submitted_data['GrantSponsor']['sponsor_address2'] ||
                         $sponsor_data_existing->sponsor_city          != $submitted_data['GrantSponsor']['sponsor_city'] ||
                         $sponsor_data_existing->sponsor_state         != $submitted_data['GrantSponsor']['sponsor_state'] ||
                         $sponsor_data_existing->sponsor_zip           != $submitted_data['GrantSponsor']['sponsor_zip'] ||
                         $sponsor_data_existing->sponsor_country       != $submitted_data['GrantSponsor']['sponsor_country'] ||
                         $sponsor_data_existing->sponsor_url           != $submitted_data['GrantSponsor']['sponsor_url'] ||
                         $sponsor_data_existing->grant_sponsor_type_id != $submitted_data['GrantSponsor']['grant_sponsor_type_id'] ||
                         $sponsor_data_existing->status                != 'A' ) {

                        //update existing sponsor data
                        $format         = array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s' );
                        $where_format   = array( '%d' );
                        $data = array(
                            'sponsor_name'          => $submitted_data['GrantSponsor']['sponsor_name'],
                            'sponsor_department'    => $submitted_data['GrantSponsor']['sponsor_department'],
                            'sponsor_address'       => $submitted_data['GrantSponsor']['sponsor_address'],
                            'sponsor_address2'      => $submitted_data['GrantSponsor']['sponsor_address2'],
                            'sponsor_city'          => $submitted_data['GrantSponsor']['sponsor_city'],
                            'sponsor_state'         => $submitted_data['GrantSponsor']['sponsor_state'],
                            'sponsor_zip'           => $submitted_data['GrantSponsor']['sponsor_zip'],
                            'sponsor_country'       => $submitted_data['GrantSponsor']['sponsor_country'],
                            'sponsor_url'           => $submitted_data['GrantSponsor']['sponsor_url'],
                            'grant_sponsor_type_id' => $submitted_data['GrantSponsor']['grant_sponsor_type_id'],
                            'status'                => 'A',
                            'updated_at'            => $current_time
                        );
                        $where = array(
                            'id'                    => $sponsor_ID,
                        );
                        if ( $wpdb->update( $table, $data, $where, $format, $where_format ) ) {

                            //log add/update of sponsor
                            //write to log
                            self::log_update ( $log_id, get_current_user_id(), $grant_ID, $sponsor_ID );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 1658USP]", "grant_id"=>$grant_ID);
                        };
                    }

                } else { //new sponsor created

                    //create new sponsor in sponsors table
                    $format = array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%s' );
                    $data = array(
                        'sponsor_name'          => $submitted_data['GrantSponsor']['sponsor_name'],
                        'sponsor_department'    => $submitted_data['GrantSponsor']['sponsor_department'],
                        'sponsor_address'       => $submitted_data['GrantSponsor']['sponsor_address'],
                        'sponsor_address2'      => $submitted_data['GrantSponsor']['sponsor_address2'],
                        'sponsor_city'          => $submitted_data['GrantSponsor']['sponsor_city'],
                        'sponsor_state'         => $submitted_data['GrantSponsor']['sponsor_state'],
                        'sponsor_zip'           => $submitted_data['GrantSponsor']['sponsor_zip'],
                        'sponsor_country'       => $submitted_data['GrantSponsor']['sponsor_country'],
                        'sponsor_url'           => $submitted_data['GrantSponsor']['sponsor_url'],
                        'grant_sponsor_type_id' => $submitted_data['GrantSponsor']['grant_sponsor_type_id'],
                        'status'                => 'A',
                        'updated_at'            => $current_time,
                        'created_at'            => $current_time
                    );
                    if ( $wpdb->insert($table, $data, $format) ) {

                        $sponsor_ID = $wpdb->insert_id;

                        //log add/update of sponsor
                        //write to log
                        self::log_update ( $log_id, get_current_user_id(), $grant_ID, $sponsor_ID );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    } else {
                        return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 1692ISP]", "grant_id"=>$grant_ID);
                    };

                }
            } else {
                return array("success"=>false, "msg"=>"Record has NOT been added. Please enter valid data into Sponsor fields.", "grant_id"=>$grant_ID);
            }

            //contacts table submission
            $table_gc = $wpdb->prefix . 'gs_grant_contacts';
            $format_gc = array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' );
            $table_gscm = $wpdb->prefix . 'gs_grant_sponsor_contact_mappings';
            $format_gscm = array( '%d','%d','%d','%s','%s' );
            for ($i = 0; $i< count( $submitted_data['contact']['contact_name'] ); $i++) {
                $data_gc = array(
                    'contact_name'      => $submitted_data['contact']['contact_name'][$i],
                    'contact_title'     => $submitted_data['contact']['contact_title'][$i],
                    'contact_org_dept'  => $submitted_data['contact']['contact_org_dept'][$i],
                    'contact_address1'  => $submitted_data['contact']['contact_address1'][$i],
                    'contact_address2'  => $submitted_data['contact']['contact_address2'][$i],
                    'contact_city'      => $submitted_data['contact']['contact_city'][$i],
                    'contact_state'     => $submitted_data['contact']['contact_state'][$i],
                    'country'           => $submitted_data['contact']['country'][$i],
                    'contact_zip'       => $submitted_data['contact']['contact_zip'][$i],
                    'contact_phone_1'   => $submitted_data['contact']['contact_phone_1'][$i],
                    'contact_phone_2'   => $submitted_data['contact']['contact_phone_2'][$i],
                    'contact_fax'       => $submitted_data['contact']['contact_fax'][$i],
                    'contact_email_1'   => $submitted_data['contact']['contact_email_1'][$i],
                    'contact_email_2'   => $submitted_data['contact']['contact_email_2'][$i],
                    'updated_at'        => $current_time,
                    'created_at'        => $current_time
                );
                if ( $wpdb->insert($table_gc, $data_gc, $format_gc) ) {

                    $contact_ID = $wpdb->insert_id;

                    //insert entry into sponsor_contact_mappings table
                    if ( !empty($contact_ID) && !empty($sponsor_ID) && !empty($grant_ID) ) {
                        $data_gscm = array(
                            'grant_id'      => $grant_ID,
                            'sponsor_id'    => $sponsor_ID,
                            'contact_id'    => $contact_ID,
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table_gscm, $data_gscm, $format_gscm)) {
                            $sponsor_contact_mapping_ID = $wpdb->insert_id;

                            //log add/update of sponsor
                            //write to log
                            self::log_update ( $log_id, get_current_user_id(), $grant_ID, $sponsor_ID );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1637GMI]", "grant_id"=>$grant_ID);
                        };
                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1715CSG]", "grant_id"=>$grant_ID);
                    };

                } else {
                    return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 1721CII]", "grant_id"=>$grant_ID);
                };
            }

            //if deadlines have been submitted  - Deadlines and Key Dates section
            if ( !empty($submitted_data['deadline']['month']) ) {

                //deadlines (key dates) table submission
                $table = $wpdb->prefix . 'gs_grant_key_dates';
                $format = array( '%d','%s','%d','%d','%s','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['deadline']['month'] ); $i++) {
                    if ( !empty($submitted_data['deadline']['month'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'date_title'    => 'deadline',
                            'month'         => $submitted_data['deadline']['month'][$i],
                            'date'          => $submitted_data['deadline']['day'][$i],
                            'satisfied'     => $submitted_data['deadline']['satisfied'][$i],
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $deadline_ID = $wpdb->insert_id;

                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_ID );
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1771DLI]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if key dates have been submitted  - Deadlines and Key Dates section
            if ( !empty($submitted_data['keydates']['month']) ) {

                //deadlines (key dates) table submission
                $table = $wpdb->prefix . 'gs_grant_key_dates';
                $format = array( '%d','%s','%d','%d','%s','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['keydates']['month'] ); $i++) {
                    if ( !empty($submitted_data['keydates']['month'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'date_title'    => $submitted_data['keydates']['date_title'][$i],
                            'month'         => $submitted_data['keydates']['month'][$i],
                            'date'          => $submitted_data['keydates']['day'][$i],
                            'satisfied'     => '',
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $keydate_ID = $wpdb->insert_id;

                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_ID );
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1797KDI]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if segment (book) codes have been submitted  - Segment (Book) Codes section
            if ( !empty($submitted_data['GrantSegmentMappings']['segment_id']) ) {

                //segment mappings table submission
                $table = $wpdb->prefix . 'gs_grant_segment_mappings';
                $format = array( '%d','%d','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['GrantSegmentMappings']['segment_id'] ); $i++) {
                    if ( !empty($submitted_data['GrantSegmentMappings']['segment_id'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'segment_id'    => $submitted_data['GrantSegmentMappings']['segment_id'][$i],
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $segment_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1820SMI]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if program types have been submitted  - Program Type section
            if ( !empty($submitted_data['GrantProgramMappings']['program_id']) ) {

                //program mappings table submission
                $table = $wpdb->prefix . 'gs_grant_program_mappings';
                $format = array( '%d','%d','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['GrantProgramMappings']['program_id'] ); $i++) {
                    if ( !empty($submitted_data['GrantProgramMappings']['program_id'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'program_id'    => $submitted_data['GrantProgramMappings']['program_id'][$i],
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $program_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1843PMI]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if subject headings have been submitted  - Subject Headings section
            if ( !empty($submitted_data['GrantSubjectMappings']['subject_title2']) ) {

                //subject mappings table submission
                $table = $wpdb->prefix . 'gs_grant_subject_mappings';
                $format = array( '%d','%d','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['GrantSubjectMappings']['subject_title2'] ); $i++) {
                    if ( !empty($submitted_data['GrantSubjectMappings']['subject_title2'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'subject_id'    => $submitted_data['GrantSubjectMappings']['subject_title2'][$i],
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $subject_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1866SBJ]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if target populations have been submitted
            if ( !empty($submitted_data['GrantTargetMappings']['target_title']) ) {

                //target mappings table submission
                $table = $wpdb->prefix . 'gs_grant_target_mappings';
                $format = array( '%d','%d','%s','%s' );
                for ($i = 0; $i < count( $submitted_data['GrantTargetMappings']['target_title'] ); $i++) {
                    if ( !empty($submitted_data['GrantTargetMappings']['target_title'][$i]) && !empty($grant_ID) ) {
                        $data = array(
                            'grant_id'      => $grant_ID,
                            'target_id'     => $submitted_data['GrantTargetMappings']['target_title'][$i],
                            'updated_at'    => $current_time,
                            'created_at'    => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $target_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1889TPI]", "grant_id"=>$grant_ID);
                        };
                    }
                }
            }

            //if revisit date has been submitted - Deadlines and Key Dates section
            if ( !empty($submitted_data['revisit']['month']) ) {

                //revisit date (key dates) table submission
                $table = $wpdb->prefix . 'gs_grant_key_dates';
                $format = array( '%d','%s','%d','%d','%d','%s','%s','%s' );
                if ( !empty($grant_ID) ) {
                    $data = array(
                        'grant_id'      => $grant_ID,
                        'date_title'    => 'revisit',
                        'year'          => $submitted_data['revisit']['year'],
                        'month'         => $submitted_data['revisit']['month'],
                        'date'          => $submitted_data['revisit']['day'],
                        'satisfied'     => '',
                        'updated_at'    => $current_time,
                        'created_at'    => $current_time
                    );
                    if ($wpdb->insert($table, $data, $format)) {
                        $revisit_date_ID = $wpdb->insert_id;

                        //log add/update of sponsor
                        //write to log
                        $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_ID );
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1914RDI]", "grant_id"=>$grant_ID);
                    };
                }
            }

            //SUCCESS!
            return array("success" => true, "msg" => "Grant has been successfully added (<a href=\"/editor/records/view/?gid=$grant_ID\">view</a>)");

        } else {
            return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 1920GID]", "grant_id"=>$grant_ID);
        }

    }


    /**
     * Function update_grant_record
     * Updates data for submitted grant record in database
     * @params  $grant_id
     *          $submitted_data
     * @return  array (success=>[true if successful, false if failed], msg=>[message for user])
     */
    function update_grant_record( $grant_id, $submitted_data )
    {
//        echo "<pre>";
//        print_r($submitted_data);
//        echo "</pre>";

        if ( !current_user_can('edit_grants') ) {
            return array("success"=>false, "msg"=>"Sorry, you are not authorized to edit grants.");
        }

        global $wpdb;

        $current_time = date("Y-m-d H:i:s");

        if ( !GrantSelectSearchAddOn::grant_exists( $grant_id ) ) {
            return array("success"=>false, "msg"=>"ERROR: Grant record #" . $grant_id ." was not found and therefore could not be updated.", "grant_id"=>$grant_id);
        }

        //format "amount" data
        if (!empty($submitted_data['grant']['amount_min'])) {
            $temp = $submitted_data['grant']['amount_min'];
            $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
            $out  = '';
            foreach ($temp as $t) {
                if (preg_match("/\d+|\,|\./", $t)) {
                    $out .= $t;
                }
            }
            $submitted_data['grant']['amount_min'] = preg_replace("/\,/", '', $out);
        }
        elseif ($submitted_data['grant']['amount_min'] == '0') {
            $submitted_data['grant']['amount_min'] = '0.00';
        }
        else {
            $submitted_data['grant']['amount_min'] = NULL;
        }
        if (!empty($submitted_data['grant']['amount_max'])) {
            $temp = $submitted_data['grant']['amount_max'];
            $temp = preg_split('//', $temp, -1, PREG_SPLIT_NO_EMPTY);
            $out = '';
            foreach ($temp as $t) {
                if (preg_match("/\d+|\,|\./", $t)) {
                    $out .= $t;
                }
            }
            $submitted_data['grant']['amount_max'] = preg_replace("/\,/", '', $out);
        }
        else {
            $submitted_data['grant']['amount_max'] = NULL;
        }

        //set email alert db value based on submitted value
        if ($submitted_data['grant']['email_alerts']){
            $submitted_data['grant']['email_alerts'] = '1';
        } else{
            $submitted_data['grant']['email_alerts'] = '0';
        }

        $table = $wpdb->prefix . 'gs_grants';
        $data = array(
            'title'             => $submitted_data['grant']['title'],
            'description'       => $submitted_data['grant']['description'],
            'requirements'      => $submitted_data['grant']['requirements'],
            'restrictions'      => $submitted_data['grant']['restrictions'],
            'samples'           => $submitted_data['grant']['samples'],
            'cfda'              => $submitted_data['grant']['cfda'],
            'amount_currency'   => $submitted_data['grant']['amount_currency'],
            'amount_min'        => $submitted_data['grant']['amount_min'],
            'amount_max'        => $submitted_data['grant']['amount_max'],
            'amount_notes'      => $submitted_data['grant']['amount_notes'],
            'grant_url_1'       => $submitted_data['grant']['grant_url_1'],
            'grant_url_2'       => $submitted_data['grant']['grant_url_2'],
            'status'            => $submitted_data['grant']['status'],
            'email_alerts'      => $submitted_data['grant']['email_alerts'],
            'updated_at'        => $current_time
        );
        $where = array(
            'id'                => $grant_id,
        );
        $format = array( '%s','%s','%s','%s','%s','%s','%s','%f','%f','%s','%s','%s','%s','%d','%s' );
        $where_format   = array( '%d' );

        if ( $wpdb->update($table, $data, $where, $format, $where_format) ) {

            //write to log
            $log_id = self::log_write( get_current_user_id(), $grant_id );

            //Update Geo Restrictions
            if (empty($submitted_data['GrantGeoLocation']['geo_location2'][0])) {
                $submitted_data['GrantGeoLocation']['geo_location2'][0] = 1;    // assign "Domestic - All States" as default
            }
            $table = $wpdb->prefix . 'gs_grant_geo_mappings';

            //Remove all existing entries in GEO Mapping table for this grant
            $where = array(
                'grant_id' => $grant_id
            );
            $where_format = array('%d');
            if (false === $wpdb->delete($table, $where, $where_format)) {
                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2176GMD]", "grant_id" => $grant_id);
            };

            //Insert new Geo Mapping entries for this grant
            $format = array('%d', '%d', '%s', '%s');
            if ( !empty($submitted_data['GrantGeoLocation']['geo_location2']) ) {
                foreach ($submitted_data['GrantGeoLocation']['geo_location2'] as $geo_id) {
                    if (!empty($geo_id)) {
                        $data = array(
                            'grant_id' => $grant_id,
                            'geo_id' => $geo_id,
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $geo_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2185GMU]", "grant_id" => $grant_id);
                        };
                    }
                }
            }

            //Update Sponsor data
            if (!empty($submitted_data['GrantSponsor']['sponsor_name'])) {

                $table = $wpdb->prefix . 'gs_grant_sponsors';

//                echo "AS: " . $submitted_data['all_sponsors'] . "<br>";

                if (isset($submitted_data['all_sponsors']) and $submitted_data['all_sponsors'] != '') { //existing sponsor selected
                    $params_array = explode('@', $submitted_data['all_sponsors']);
                    $sponsor_ID = (int)$params_array[0];

//                    echo "SID: $sponsor_ID<br>";

                    //get existing sponsor data
                    $sponsor_data_existing = GrantSelectSearchAddOn::get_sponsor_data($sponsor_ID);

//                    echo "SDE:<br>";
//                    echo "<pre>";
//                    print_r($sponsor_data_existing);
//                    echo "</pre>";
//
//                    echo "SDE:<br>";
//                    echo "<pre>";
//                    print_r($submitted_data);
//                    echo "</pre>";

                    //compare existing sponsor data to submitted data
                    if ($sponsor_data_existing->sponsor_name != $submitted_data['GrantSponsor']['sponsor_name'] ||
                        $sponsor_data_existing->sponsor_department != $submitted_data['GrantSponsor']['sponsor_department'] ||
                        $sponsor_data_existing->sponsor_address != $submitted_data['GrantSponsor']['sponsor_address'] ||
                        $sponsor_data_existing->sponsor_address2 != $submitted_data['GrantSponsor']['sponsor_address2'] ||
                        $sponsor_data_existing->sponsor_city != $submitted_data['GrantSponsor']['sponsor_city'] ||
                        $sponsor_data_existing->sponsor_state != $submitted_data['GrantSponsor']['sponsor_state'] ||
                        $sponsor_data_existing->sponsor_zip != $submitted_data['GrantSponsor']['sponsor_zip'] ||
                        $sponsor_data_existing->sponsor_country != $submitted_data['GrantSponsor']['sponsor_country'] ||
                        $sponsor_data_existing->sponsor_url != $submitted_data['GrantSponsor']['sponsor_url'] ||
                        $sponsor_data_existing->grant_sponsor_type_id != $submitted_data['GrantSponsor']['grant_sponsor_type_id'] ||
                        $sponsor_data_existing->status != 'A'
                    ) {

                    //echo "update existing sponsor data<br>";

                        //update existing sponsor data
                        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s');
                        $where_format = array('%d');
                        $data = array(
                            'sponsor_name' => $submitted_data['GrantSponsor']['sponsor_name'],
                            'sponsor_department' => $submitted_data['GrantSponsor']['sponsor_department'],
                            'sponsor_address' => $submitted_data['GrantSponsor']['sponsor_address'],
                            'sponsor_address2' => $submitted_data['GrantSponsor']['sponsor_address2'],
                            'sponsor_city' => $submitted_data['GrantSponsor']['sponsor_city'],
                            'sponsor_state' => $submitted_data['GrantSponsor']['sponsor_state'],
                            'sponsor_zip' => $submitted_data['GrantSponsor']['sponsor_zip'],
                            'sponsor_country' => $submitted_data['GrantSponsor']['sponsor_country'],
                            'sponsor_url' => $submitted_data['GrantSponsor']['sponsor_url'],
                            'grant_sponsor_type_id' => $submitted_data['GrantSponsor']['grant_sponsor_type_id'],
                            'status' => 'A',
                            'updated_at' => $current_time
                        );
                        $where = array(
                            'id' => $sponsor_ID,
                        );
                        if ($wpdb->update($table, $data, $where, $format, $where_format)) {

                            //log add/update of sponsor
                            //write to log
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $sponsor_ID );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2248USP]", "grant_id" => $grant_id);
                        };
                    }

                } else { //new sponsor created

                    //create new sponsor in sponsors table
                    $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s');
                    $data = array(
                        'sponsor_name' => $submitted_data['GrantSponsor']['sponsor_name'],
                        'sponsor_department' => $submitted_data['GrantSponsor']['sponsor_department'],
                        'sponsor_address' => $submitted_data['GrantSponsor']['sponsor_address'],
                        'sponsor_address2' => $submitted_data['GrantSponsor']['sponsor_address2'],
                        'sponsor_city' => $submitted_data['GrantSponsor']['sponsor_city'],
                        'sponsor_state' => $submitted_data['GrantSponsor']['sponsor_state'],
                        'sponsor_zip' => $submitted_data['GrantSponsor']['sponsor_zip'],
                        'sponsor_country' => $submitted_data['GrantSponsor']['sponsor_country'],
                        'sponsor_url' => $submitted_data['GrantSponsor']['sponsor_url'],
                        'grant_sponsor_type_id' => $submitted_data['GrantSponsor']['grant_sponsor_type_id'],
                        'status' => 'A',
                        'updated_at' => $current_time,
                        'created_at' => $current_time
                    );
                    if ($wpdb->insert($table, $data, $format)) {

                        $sponsor_ID = $wpdb->insert_id;

                        //log add/update of sponsor
                        //write to log
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $sponsor_ID );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 1692ISP]", "grant_id" => $grant_id);
                    };

                }

                //update grant_sponsor_contact_mappings table with new sponsor
                $table_gscm = $wpdb->prefix . 'gs_grant_sponsor_contact_mappings';
                $format_sponsor_update = array('%d', '%s');
                $data_sponsor_update = array(
                    'sponsor_id' => $sponsor_ID,
                    'updated_at' => $current_time
                );
                $where_format_sponsor_update = array('%d');
                $where_sponsor_update = array(
                    'grant_id' => $grant_id,
                );

//                echo "TCSCM: $table_gscm<br>";
//                echo "DSU: $data_sponsor_update<br>";
//                echo "WSU: $where_sponsor_update<br>";
//                echo "WFSU: $where_format_sponsor_update<br>";

                if ($wpdb->update($table_gscm, $data_sponsor_update, $where_sponsor_update, $format_sponsor_update, $where_format_sponsor_update)) {

                    //log add/update of sponsor
                    //write to log
                    $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                    self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                    //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                } else {
                    return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2817UGCM]", "grant_id" => $grant_id);
                };


            } else {
                return array("success" => false, "msg" => "Error. Please enter valid Sponsor Name.", "grant_id" => $grant_ID);
            }

            //Update Contacts
            $submitted_contact_ids = $submitted_data['contact']['id'];
            $database_contact_ids = GrantSelectSearchAddOn::get_contact_ids($grant_id);

//            echo "<pre>";   //debug
//            print_r($submitted_contact_ids);
//            echo "</pre>";
//            echo "<pre>";   //debug
//            print_r($database_contact_ids);
//            echo "</pre>";

            $table_gc = $wpdb->prefix . 'gs_grant_contacts';
            $table_gscm = $wpdb->prefix . 'gs_grant_sponsor_contact_mappings';

            //Delete any contacts that are in database whose id is not included in the submitted data
            if ( !empty($database_contact_ids) ) {
                foreach ($database_contact_ids as $key => $value) {
                    if (!in_array($value['contact_id'], $submitted_contact_ids)) {

                        //delete sponsor_contact_mapping from database
                        $where = array(
                            'grant_id' => $grant_id,
                            'contact_id' => $value['contact_id']
                        );
                        $where_format = array('%d', '%d');
                        if (false === $wpdb->delete($table_gscm, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2317GCD]", "grant_id" => $grant_id);
                        };

                        //delete contact from database
                        $where = array(
                            'id' => $value['contact_id']
                        );
                        $where_format = array('%d');
                        if (false === $wpdb->delete($table_gc, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2326GCD]", "grant_id" => $grant_id);
                        };

                        //log add/update of sponsor
                        //write to log
                        $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    }
                }
            }

            for ($i = 0; $i < count($submitted_data['contact']['contact_name']); $i++) {
                if (empty ($submitted_data['contact']['id'][$i])) {
                    //For each contact submitted without an id, create a new contact in database (incl. entry in mapping table)
                    $data_gc = array(
                        'contact_name' => $submitted_data['contact']['contact_name'][$i],
                        'contact_title' => $submitted_data['contact']['contact_title'][$i],
                        'contact_org_dept' => $submitted_data['contact']['contact_org_dept'][$i],
                        'contact_address1' => $submitted_data['contact']['contact_address1'][$i],
                        'contact_address2' => $submitted_data['contact']['contact_address2'][$i],
                        'contact_city' => $submitted_data['contact']['contact_city'][$i],
                        'contact_state' => $submitted_data['contact']['contact_state'][$i],
                        'country' => $submitted_data['contact']['country'][$i],
                        'contact_zip' => $submitted_data['contact']['contact_zip'][$i],
                        'contact_phone_1' => $submitted_data['contact']['contact_phone_1'][$i],
                        'contact_phone_2' => $submitted_data['contact']['contact_phone_2'][$i],
                        'contact_fax' => $submitted_data['contact']['contact_fax'][$i],
                        'contact_email_1' => $submitted_data['contact']['contact_email_1'][$i],
                        'contact_email_2' => $submitted_data['contact']['contact_email_2'][$i],
                        'updated_at' => $current_time,
                        'created_at' => $current_time
                    );
                    $format_gc = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
                    if ($wpdb->insert($table_gc, $data_gc, $format_gc)) {

                        $contact_ID = $wpdb->insert_id;

                        //insert entry into sponsor_contact_mappings table
                        if (!empty($contact_ID) && !empty($sponsor_ID) && !empty($grant_id)) {
                            $format_gscm = array('%d', '%d', '%d', '%s', '%s');
                            $data_gscm = array(
                                'grant_id' => $grant_id,
                                'sponsor_id' => $sponsor_ID,
                                'contact_id' => $contact_ID,
                                'updated_at' => $current_time,
                                'created_at' => $current_time
                            );
                            if ($wpdb->insert($table_gscm, $data_gscm, $format_gscm)) {
                                $sponsor_contact_mapping_ID = $wpdb->insert_id;

                                //log add/update of sponsor
                                //write to log
                                $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                                self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                                //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                            } else {
                                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2371GMI]", "grant_id" => $grant_id);
                            };
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2374CSG]", "grant_id" => $grant_id);
                        };

                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2378CII]", "grant_id" => $grant_id);
                    };

                } else {
                    //For each contact submitted with an id, check if the data has been changed. If so, update it in the database
                    $contact_ID = $submitted_data['contact']['id'][$i];

                    //get existing contact data
                    $contact_data_existing = GrantSelectSearchAddOn::get_contact_data($contact_ID);

                    //compare existing contact data to submitted data
                    if ($contact_data_existing->contact_name != $submitted_data['contact']['contact_name'][$i] ||
                        $contact_data_existing->contact_title != $submitted_data['contact']['contact_title'][$i] ||
                        $contact_data_existing->contact_org_dept != $submitted_data['contact']['contact_org_dept'][$i] ||
                        $contact_data_existing->contact_address1 != $submitted_data['contact']['contact_address1'][$i] ||
                        $contact_data_existing->contact_address2 != $submitted_data['contact']['contact_address2'][$i] ||
                        $contact_data_existing->contact_city != $submitted_data['contact']['contact_city'][$i] ||
                        $contact_data_existing->contact_state != $submitted_data['contact']['contact_state'][$i] ||
                        $contact_data_existing->country != $submitted_data['contact']['country'][$i] ||
                        $contact_data_existing->contact_zip != $submitted_data['contact']['contact_zip'][$i] ||
                        $contact_data_existing->contact_phone_1 != $submitted_data['contact']['contact_phone_1'][$i] ||
                        $contact_data_existing->contact_phone_2 != $submitted_data['contact']['contact_phone_2'][$i] ||
                        $contact_data_existing->contact_fax != $submitted_data['contact']['contact_fax'][$i] ||
                        $contact_data_existing->contact_email_1 != $submitted_data['contact']['contact_email_1'][$i] ||
                        $contact_data_existing->contact_email_2 != $submitted_data['contact']['contact_email_2'][$i]
                    ) {

                        //update existing contact data
                        $format = array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s');
                        $where_format = array('%d');
                        $data = array(
                            'contact_name' => $submitted_data['contact']['contact_name'][$i],
                            'contact_title' => $submitted_data['contact']['contact_title'][$i],
                            'contact_org_dept' => $submitted_data['contact']['contact_org_dept'][$i],
                            'contact_address1' => $submitted_data['contact']['contact_address1'][$i],
                            'contact_address2' => $submitted_data['contact']['contact_address2'][$i],
                            'contact_city' => $submitted_data['contact']['contact_city'][$i],
                            'contact_state' => $submitted_data['contact']['contact_state'][$i],
                            'country' => $submitted_data['contact']['country'][$i],
                            'contact_zip' => $submitted_data['contact']['contact_zip'][$i],
                            'contact_phone_1' => $submitted_data['contact']['contact_phone_1'][$i],
                            'contact_phone_2' => $submitted_data['contact']['contact_phone_2'][$i],
                            'contact_fax' => $submitted_data['contact']['contact_fax'][$i],
                            'contact_email_1' => $submitted_data['contact']['contact_email_1'][$i],
                            'contact_email_2' => $submitted_data['contact']['contact_email_2'][$i],
                            'updated_at' => $current_time
                        );
                        $where = array(
                            'id' => $contact_ID,
                        );
                        if ($wpdb->update($table_gc, $data, $where, $format, $where_format)) {

                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2434UGC]", "grant_id" => $grant_id);
                        };
                    }

                }
            }

            //Update deadlines
            $submitted_deadline_ids = $submitted_data['deadline']['id'];
            $database_deadline_ids = GrantSelectSearchAddOn::get_deadline_ids($grant_id);

//            echo "<pre>";   //debug
//            print_r($submitted_deadline_ids);
//            echo "</pre>";
//            echo "<pre>";   //debug
//            print_r($database_deadline_ids);
//            echo "</pre>";

            $table = $wpdb->prefix . 'gs_grant_key_dates';

            //Delete any deadlines that are in database whose id is not included in the submitted data
            if ( !empty($database_deadline_ids) ) {
                foreach ($database_deadline_ids as $key => $value) {
                    if (!in_array($value['id'], $submitted_deadline_ids)) {

                        //delete deadline key date from database
                        $where = array(
                            'grant_id' => $grant_id,
                            'date_title' => 'deadline',
                            'id' => $value['id']
                        );
                        $where_format = array('%d', '%s', '%d');
                        if (false === $wpdb->delete($table, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2462GDD]", "grant_id" => $grant_id);
                        };

                        //log add/update of sponsor
                        //write to log
                        $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    }
                }
            }

            for ($i = 0; $i < count($submitted_data['deadline']['month']); $i++) {
                if (empty ($submitted_data['deadline']['id'][$i])) {
                    //For each deadline submitted without an id, create a new deadline in database
                    if (!empty($submitted_data['deadline']['month'][$i]) ||
                        !empty($submitted_data['deadline']['day'][$i]) ||
                        !empty($submitted_data['deadline']['satisfied'][$i]) ) {

                        $data = array(
                            'grant_id' => $grant_id,
                            'date_title' => 'deadline',
                            'month' => $submitted_data['deadline']['month'][$i],
                            'date' => $submitted_data['deadline']['day'][$i],
                            'satisfied' => $submitted_data['deadline']['satisfied'][$i],
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        $format = array('%d', '%s', '%d', '%d', '%s', '%s', '%s');
                        if ($wpdb->insert($table, $data, $format)) {
                            $deadline_ID = $wpdb->insert_id;

                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2485DLI]", "grant_id" => $grant_id);
                        };
                    }
                } else {

                    //If any submitted deadline has been reset (i.e. is empty), delete it
                    if (empty($submitted_data['deadline']['month'][$i]) && empty($submitted_data['deadline']['day'][$i]) && empty($submitted_data['deadline']['satisfied'][$i])) {
                        $where = array(
                            'grant_id' => $grant_id,
                            'id' => $submitted_data['deadline']['id'][$i]
                        );
                        $where_format = array('%d','%d');
                        if (false === $wpdb->delete($table, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2507DLD]", "grant_id" => $grant_id);
                        } else {
                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor($grant_id);
                            self::log_update($log_id, get_current_user_id(), $grant_id, $log_sponsor_id);
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);
                        };
                    } else {
                        //For each deadline submitted with an id, check if the data has been changed. If so, update it in the database
                        $deadline_ID = $submitted_data['deadline']['id'][$i];

                        //get existing deadline data
                        $deadline_data_existing = GrantSelectSearchAddOn::get_deadline_data($deadline_ID);

//                    echo "DDE:<br>";
//                    echo "<pre>";   //debug
//                    print_r($deadline_data_existing);
//                    echo "</pre>";
//
//                    echo "SDD:<br>";
//                    echo "<pre>";   //debug
//                    print_r($submitted_data['deadline']);
//                    echo "</pre>";

                        //compare existing deadline data to submitted data
                        if ($deadline_data_existing[0]->month != $submitted_data['deadline']['month'][$i] ||
                            $deadline_data_existing[0]->date != $submitted_data['deadline']['day'][$i] ||
                            $deadline_data_existing[0]->satisfied != $submitted_data['deadline']['satisfied'][$i]
                        ) {

                            //update existing deadline data
                            $format = array('%d', '%d', '%s', '%s');
                            $where_format = array('%d', '%d', '%s');

                            $data = array(
                                'month' => $submitted_data['deadline']['month'][$i],
                                'date' => $submitted_data['deadline']['day'][$i],
                                'satisfied' => $submitted_data['deadline']['satisfied'][$i],
                                'updated_at' => $current_time
                            );
                            $where = array(
                                'id' => $deadline_ID,
                                'grant_id' => $grant_id,
                                'date_title' => 'deadline'
                            );
                            if ($wpdb->update($table, $data, $where, $format, $where_format)) {

                                //log add/update of deadline
                                //write to log
                                $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                                self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                                //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                            } else {
                                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2520UDL]", "grant_id" => $grant_id);
                            };
                        }
                    }
                }
            }

            //Update key dates
            $submitted_keydates_ids = $submitted_data['keydates']['id'];
            $database_keydates_ids = GrantSelectSearchAddOn::get_keydates_ids($grant_id);

//            echo "<pre>";   //debug
//            print_r($submitted_keydates_ids);
//            echo "</pre>";
//            echo "<pre>";   //debug
//            print_r($database_keydates_ids);
//            echo "</pre>";

            $table = $wpdb->prefix . 'gs_grant_key_dates';

            //Delete any keydates that are in database whose id is not included in the submitted data
            if ( !empty($database_keydates_ids) ) {
                foreach ($database_keydates_ids as $key => $value) {
                    if (!in_array($value['id'], $submitted_keydates_ids)) {

                        //delete keydates from database
                        $where = array(
                            'grant_id' => $grant_id,
                            'id' => $value['id']
                        );
                        $where_format = array('%d', '%d');
                        if (false === $wpdb->delete($table, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2526KDD]", "grant_id" => $grant_id);
                        } else {
                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor($grant_id);
                            self::log_update($log_id, get_current_user_id(), $grant_id, $log_sponsor_id);
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);
                        };
                    }
                }
            }

            for ($i = 0; $i < count($submitted_data['keydates']['month']); $i++) {
                if (empty ($submitted_data['keydates']['id'][$i])) {
                    //For each keydate submitted without an id, create a new keydate in database
                    if ( !empty($submitted_data['keydates']['date_title'][$i]) ||
                         !empty($submitted_data['keydates']['month'][$i]) ||
                         !empty($submitted_data['keydates']['day'][$i]) ) {

                        $data = array(
                            'grant_id' => $grant_id,
                            'date_title' => $submitted_data['keydates']['date_title'][$i],
                            'month' => $submitted_data['keydates']['month'][$i],
                            'date' => $submitted_data['keydates']['day'][$i],
                            'satisfied' => $submitted_data['keydates']['satisfied'][$i],
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        $format = array('%d', '%s', '%d', '%d', '%s', '%s', '%s');
                        if ($wpdb->insert($table, $data, $format)) {
                            $keydates_ID = $wpdb->insert_id;

                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                            self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2585KDI]", "grant_id" => $grant_id);
                        };
                    }
                } else {

                    //If any submitted keydate have been reset (i.e. is empty), delete it
                    if (empty($submitted_data['keydates']['date_title'][$i]) && empty($submitted_data['keydates']['month'][$i]) && empty($submitted_data['keydates']['day'][$i])) {
                        $where = array(
                            'grant_id' => $grant_id,
                            'id' => $submitted_data['keydates']['id'][$i]
                        );
                        $where_format = array('%d','%d');
                        if (false === $wpdb->delete($table, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2614KDD]", "grant_id" => $grant_id);
                        } else {
                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor($grant_id);
                            self::log_update($log_id, get_current_user_id(), $grant_id, $log_sponsor_id);
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);
                        };
                    } else {
                        //For each keydate submitted with an id, check if the data has been changed. If so, update it in the database
                        $keydates_ID = $submitted_data['keydates']['id'][$i];

                        //get existing keydates data
                        $keydates_data_existing = GrantSelectSearchAddOn::get_keydates_data($keydates_ID);

//                    echo "DDE:<br>";
//                    echo "<pre>";   //debug
//                    print_r($keydates_data_existing);
//                    echo "</pre>";
//
//                    echo "SDD:<br>";
//                    echo "<pre>";   //debug
//                    print_r($submitted_data['keydates']);
//                    echo "</pre>";

                        //compare existing keydates data to submitted data
                        if ($keydates_data_existing[0]->date_title != $submitted_data['keydates']['date_title'][$i] ||
                            $keydates_data_existing[0]->month != $submitted_data['keydates']['month'][$i] ||
                            $keydates_data_existing[0]->date != $submitted_data['keydates']['day'][$i] ||
                            $keydates_data_existing[0]->satisfied != $submitted_data['keydates']['satisfied'][$i]
                        ) {

                            //update existing keydates data
                            $format = array('%s', '%d', '%d', '%s', '%s');
                            $where_format = array('%d', '%d');

                            $data = array(
                                'date_title' => $submitted_data['keydates']['date_title'][$i],
                                'month' => $submitted_data['keydates']['month'][$i],
                                'date' => $submitted_data['keydates']['day'][$i],
                                'satisfied' => $submitted_data['keydates']['satisfied'][$i],
                                'updated_at' => $current_time
                            );
                            $where = array(
                                'id' => $keydates_ID,
                                'grant_id' => $grant_id
                            );
                            if ($wpdb->update($table, $data, $where, $format, $where_format)) {

                                //log add/update of keydate
                                //write to log
                                $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                                self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                                //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                            } else {
                                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2634CKD]", "grant_id" => $grant_id);
                            };
                        }
                    }
                }
            }

            //Update segments
            $table = $wpdb->prefix . 'gs_grant_segment_mappings';

            //Remove all existing entries in Segment Mapping table for this grant
            $where = array(
                'grant_id' => $grant_id
            );
            $where_format = array('%d');
            if (false === $wpdb->delete($table, $where, $where_format)) {
                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2654SGD]", "grant_id" => $grant_id);
            };

            //Insert new Segment entries for this grant
            $format = array('%d', '%d', '%s', '%s');
            if ( !empty($submitted_data['GrantSegmentMappings']['segment_id']) ) {
                foreach ($submitted_data['GrantSegmentMappings']['segment_id'] as $segment_id) {
                    if (!empty($segment_id)) {
                        $data = array(
                            'grant_id' => $grant_id,
                            'segment_id' => $segment_id,
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $segment_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2670SMU]", "grant_id" => $grant_id);
                        };
                    }
                }
            }

            //Update program types
            $table = $wpdb->prefix . 'gs_grant_program_mappings';

            //Remove all existing entries in Program Mapping table for this grant
            $where = array(
                'grant_id' => $grant_id
            );
            $where_format = array('%d');
            if (false === $wpdb->delete($table, $where, $where_format)) {
                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2683PMD]", "grant_id" => $grant_id);
            };

            //Insert new Program entries for this grant
            $format = array('%d', '%d', '%s', '%s');
            if ( !empty($submitted_data['GrantProgramMappings']['program_id']) ) {
                foreach ($submitted_data['GrantProgramMappings']['program_id'] as $program_id) {
                    if (!empty($program_id)) {
                        $data = array(
                            'grant_id' => $grant_id,
                            'program_id' => $program_id,
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $segment_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2699PMU]", "grant_id" => $grant_id);
                        };
                    }
                }
            }

            //Update subject headings
            $table = $wpdb->prefix . 'gs_grant_subject_mappings';

            //Remove all existing entries in Subject Mapping table for this grant
            $where = array(
                'grant_id' => $grant_id
            );
            $where_format = array('%d');
            if (false === $wpdb->delete($table, $where, $where_format)) {
                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2714SHD]", "grant_id" => $grant_id);
            };

            //Insert new Subject entries for this grant
            $format = array('%d', '%d', '%s', '%s');
            if ( !empty($submitted_data['GrantSubjectMappings']['subject_title2']) ) {
                foreach ($submitted_data['GrantSubjectMappings']['subject_title2'] as $subject_id) {
                    if (!empty($subject_id)) {
                        $data = array(
                            'grant_id' => $grant_id,
                            'subject_id' => $subject_id,
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $subject_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2730SHU]", "grant_id" => $grant_id);
                        };
                    }
                }
            }

            //Update target populations
            $table = $wpdb->prefix . 'gs_grant_target_mappings';

            //Remove all existing entries in Target Mapping table for this grant
            $where = array(
                'grant_id' => $grant_id
            );
            $where_format = array('%d');
            if (false === $wpdb->delete($table, $where, $where_format)) {
                return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2746TPD]", "grant_id" => $grant_id);
            };

            //Insert new Target Population entries for this grant
            $format = array('%d', '%d', '%s', '%s');
            if ( !empty($submitted_data['GrantTargetMappings']['target_title']) ) {
                foreach ($submitted_data['GrantTargetMappings']['target_title'] as $target_id) {
                    if (!empty($target_id)) {
                        $data = array(
                            'grant_id' => $grant_id,
                            'target_id' => $target_id,
                            'updated_at' => $current_time,
                            'created_at' => $current_time
                        );
                        if ($wpdb->insert($table, $data, $format)) {
                            $target_mapping_ID = $wpdb->insert_id;
                        } else {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2762TPU]", "grant_id" => $grant_id);
                        };
                    }
                }
            }


            //Update revisit date
            $submitted_revisit_id = $submitted_data['revisit']['id'];
            $database_revisit_ids = GrantSelectSearchAddOn::get_revisit_ids($grant_id);

//            echo "<pre>";   //debug
//            print_r($submitted_revisit_id);
//            echo "</pre>";
//            echo "<pre>";   //debug
//            print_r($database_revisit_ids);
//            echo "</pre>";

            $table = $wpdb->prefix . 'gs_grant_key_dates';


            //If submitted revisit date has been reset (i.e. is empty), delete it
            if ( empty($submitted_data['revisit']['month']) && empty($submitted_data['revisit']['day']) && empty($submitted_data['revisit']['year']) ) {
                $where = array(
                    'grant_id' => $grant_id,
                    'date_title' => 'revisit',
                    'id' => $submitted_revisit_id
                );
                $where_format = array('%d', '%s', '%d');
                if (false === $wpdb->delete($table, $where, $where_format)) {
                    return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2808RDD]", "grant_id" => $grant_id);
                } else {
                    //log add/update of sponsor
                    //write to log
                    $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor($grant_id);
                    self::log_update($log_id, get_current_user_id(), $grant_id, $log_sponsor_id);
                    //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);
                };
            }

            //Delete any revisit dates that are in database whose id is not included in the submitted data
            if ( !empty($database_revisit_ids) ) {
                foreach ($database_revisit_ids as $key => $value) {
                    if ( $value['id'] != $submitted_revisit_id && !empty($value['id']) ) {

                        //delete revisit key date from database
                        $where = array(
                            'grant_id' => $grant_id,
                            'date_title' => 'revisit',
                            'id' => $value['id']
                        );
                        $where_format = array('%d', '%s', '%d');
                        if (false === $wpdb->delete($table, $where, $where_format)) {
                            return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2793RDD]", "grant_id" => $grant_id);
                        } else {
                            //log add/update of sponsor
                            //write to log
                            $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor($grant_id);
                            self::log_update($log_id, get_current_user_id(), $grant_id, $log_sponsor_id);
                            //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);
                        };
                    }
                }
            }

            if (empty ($submitted_data['revisit']['id'])) {
                //For each revisit date submitted without an id, create a new revisit date in database
                if ( !empty($submitted_data['revisit']['month']) || !empty($submitted_data['revisit']['day']) || !empty($submitted_data['revisit']['year']) ) {
                    $data = array(
                        'grant_id' => $grant_id,
                        'date_title' => 'revisit',
                        'month' => $submitted_data['revisit']['month'],
                        'date' => $submitted_data['revisit']['day'],
                        'year' => $submitted_data['revisit']['year'],
                        'updated_at' => $current_time,
                        'created_at' => $current_time
                    );
                    $format = array('%d', '%s', '%d', '%d', '%d', '%s', '%s');
                    if ($wpdb->insert($table, $data, $format)) {
                        $revisit_ID = $wpdb->insert_id;

                        //log add/update of sponsor
                        //write to log
                        $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2816RDI]", "grant_id" => $grant_id);
                    };
                }
            } else {

                //For each revisit date submitted with an id, check if the data has been changed. If so, update it in the database
                $revisit_ID = $submitted_data['revisit']['id'];

                //get existing deadline data
                $revisit_data_existing = GrantSelectSearchAddOn::get_revisit_data($revisit_ID);

//                echo "DDE:<br>";
//                echo "<pre>";   //debug
//                print_r($revisit_data_existing);
//                echo "</pre>";
//
//                echo "SDD:<br>";
//                echo "<pre>";   //debug
//                print_r($submitted_data['revisit']);
//                echo "</pre>";

                //compare existing revisit data to submitted data
                if ($revisit_data_existing[0]->month != $submitted_data['revisit']['month'] ||
                    $revisit_data_existing[0]->date != $submitted_data['revisit']['day'] ||
                    $revisit_data_existing[0]->year != $submitted_data['revisit']['year']
                ) {
                    //update existing revisit data
                    $format = array('%d', '%d', '%d', '%s');
                    $where_format = array('%d', '%d', '%s');

                    $data = array(
                        'month' => $submitted_data['revisit']['month'],
                        'date' => $submitted_data['revisit']['day'],
                        'year' => $submitted_data['revisit']['year'],
                        'updated_at' => $current_time
                    );
                    $where = array(
                        'id' => $revisit_ID,
                        'grant_id' => $grant_id,
                        'date_title' => 'revisit'
                    );
                    if ($wpdb->update($table, $data, $where, $format, $where_format)) {

                        //log add/update of deadline
                        //write to log
                        $log_sponsor_id = GrantSelectSearchAddOn::get_grant_sponsor( $grant_id );
                        self::log_update ( $log_id, get_current_user_id(), $grant_id, $log_sponsor_id );
                        //$this->log_sponsor_add($_SESSION['user_id'], $sponsor_ID);

                    } else {
                        return array("success" => false, "msg" => "Yikes! Something went wrong. Please try again. [Error Code 2864URD]", "grant_id" => $grant_id);
                    };
                }
            }

            //SUCCESS!
            return array("success" => true, "msg" => "Grant has been successfully updated (<a href=\"/editor/records/view/?gid=$grant_id\">view</a>)");

        } else {
            return array("success"=>false, "msg"=>"Yikes! Something went wrong. Please try again. [Error Code 2877GUD]", "grant_id"=>$grant_id);
        }
    }


    /**
     * Function log_write
     * Logs user activity in database
     * @params  $user_id
     *          $grant_id
     * @return  $log_id on success, false on fail
     */
    function log_write ( $user_id, $grant_id )
    {
        global $wpdb;

        $current_time = date("Y-m-d H:i:s");

        $table = $wpdb->prefix . 'gs_editor_transactions';
        $data = array(
            'grant_id'          => $grant_id,
            'editor_id'         => $user_id,
            'timestamp'         => $current_time,
            'updated_at'        => $current_time,
            'created_at'        => $current_time
        );
        $format = array( '%d','%d','%s','%s','%s' );
        if ( $wpdb->insert($table, $data, $format) ) {
            $log_id = $wpdb->insert_id;
            return $log_id;
        } else {
            return false;
        }
    }


    /**
     * Function log_update
     * Adds user activity to log in database
     * @params  $log_id
     *          $user_id
     *          $grant_id
     *          $sponsor_id
     * @return  true on success, false on fail
     */
    function log_update ( $log_id, $user_id, $grant_id, $sponsor_id )
    {
        global $wpdb;

        $current_time = date("Y-m-d H:i:s");

        $table = $wpdb->prefix . 'gs_editor_transactions';

        $data = array(
            'sponsor_id'        => $sponsor_id,
            'updated_at'        => $current_time
        );
        $format = array( '%d','%s' );

        //TODO: Debug why sponsor ID is always logged on Edit Grant submission
//        echo "LID: $log_id<br>";
//        echo "UID: $user_id<br>";
//        echo "GID: $grant_id<br>";
//        echo "SID: $sponsor_id<br>";

        $where = array(
            'id'                => $log_id,
            'grant_id'          => $grant_id,
            'editor_id'         => $user_id
        );
        $where_format   = array( '%d','%d','%d' );

        if ( $wpdb->update( $table, $data, $where, $format, $where_format ) ) {
            return true;
        } else {
            return false;
        }
    }




    // helper function for finding lowest id number of all active grants
    public function grantselect_get_lowest_grant_id( $status=null )
    {
        global $wpdb;

        $where_clause = '';
        if ( !empty($status) ) {
            $where_clause = "WHERE status='" . ucfirst( substr($status, 0, 1) ) . "'";
        }
        $sql_query = "SELECT id FROM " . $wpdb->prefix . "gs_grants " . $where_clause . " ORDER BY id ASC LIMIT 1";
        $sql = $wpdb->prepare( $sql_query );
        $data = $wpdb->get_results( $sql );

        $lowest_grant_id = $data[0]->id;

        return $lowest_grant_id;
    }


    // function for search results shortcode
    public function grantselect_search_display_results( $atts ) {

        $entry_id = absint( $_GET['sid'] );

        $sort_by = '';

        $page = absint( $_GET['pn'] );  //display this page of the results
        if ( empty($page) ) $page = 1;
        $perpage = absint( $_GET['pp'] );  //display result per page  of the results
        if ( empty($perpage) ) $perpage = 10;

        $current_user = get_current_user_id();

        $display_content = "";  //initialize
        $display_content .= "<div class='grantselect-search-results'>";
        $display_content .= GrantSelectSearchAddOn::search_results( $current_user, $entry_id, $page, $sort_by, $perpage, 'editor' );
        $display_content .= "</div>";

        return $display_content;
    }


    // functions for dashboard shortcodes
    public function grantselect_records_display_summary( $atts ) {

        //initialize database connection
        global $wpdb;
        $res = array();

        //default attributes
        $params = shortcode_atts( array(
            'data'      => '',
            'period'    => 'all',
            'format'    => 'text',
            'limit'     => '20'
        ), $atts );

        //user input
        $datatype   = filter_var( $params['data'], FILTER_SANITIZE_STRING );
        $period = filter_var( $params['period'], FILTER_SANITIZE_STRING );
        $format = filter_var( $params['format'], FILTER_SANITIZE_STRING );
        $limit  = filter_var( $params['limit'], FILTER_SANITIZE_NUMBER_INT );

        //process output
        $output = '---';
        switch ($datatype) {
            case 'record_total_active': //total count of active grant records

                $sql_query = "SELECT
                                  count(DISTINCT g.title) as c
                                FROM
                                  " . $wpdb->prefix . "gs_grants as g
                                WHERE
                                    g.status = 'A'";

//        echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query );
                $data = $wpdb->get_results( $sql, "OBJECT" );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

                $res = $data[0]->c;

//        echo "<pre>";   //debug
//        print_r($res);
//        echo "</pre>";

                switch ($format) {
                    case 'text':
                    case 'number_raw':
                        $output = $res;
                        break;
                    case 'number_formatted':
                        $output = number_format( $res );
                        break;
                }

                break;
            case 'records_updated': //total count of grant records that have been updated during period

                switch ( $period ) {
                    case 'today':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("today midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("tomorrow midnight") );
                        break;
                    case 'yesterday':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("yesterday midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("today midnight") );
                        break;
                    case 'this_month':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("first day of this month midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("first day of next month midnight") );
                        break;
                    case 'last_month':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("first day of last month midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("first day of this month midnight") );
                        break;
                    case 'this_year':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("first day of january this year midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("first day of january next year midnight") );
                        break;
                    case 'last_year':
                        $period_begin = gmdate( "Y-m-d H:i:s", strtotime("first day of january last year midnight") );
                        $period_end = gmdate( "Y-m-d H:i:s", strtotime("first day of january this year midnight") );
                        break;
                }

                $sql_query = "SELECT
                                  count(DISTINCT g.title) as c
                                FROM
                                  " . $wpdb->prefix . "gs_grants as g
                                WHERE
                                    g.updated_at >= CAST(%s AS DATETIME) AND g.updated_at < CAST(%s AS DATETIME)";

//                echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query, $period_begin, $period_end );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql, "OBJECT" );

//                echo "<pre>";   //debug
//                print_r($data);
//                echo "</pre>";

                $res = $data[0]->c;

//                echo "<pre>";   //debug
//                print_r($res);
//                echo "</pre>";

                switch ($format) {
                    case 'text':
                    case 'number_raw':
                        $output = $res;
                        break;
                    case 'number_formatted':
                        $output = number_format( $res );
                        break;
                }

                break;
            case 'records_by_collection':

                $sql_query = "SELECT
                                  s.segment_title, count(*) as record_count
                                FROM
                                  " . $wpdb->prefix . "gs_grant_segments as s
                                LEFT JOIN " . $wpdb->prefix . "gs_grant_segment_mappings as m ON
                                  s.id = m.segment_id
                                LEFT JOIN " . $wpdb->prefix . "gs_grants as g ON
                                  g.id = m.grant_id
                                WHERE
                                    g.status = 'A'
                                GROUP BY
                                    s.segment_title
                                ORDER BY
                                    s.segment_title ASC";

//                echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql, "OBJECT" );

//                echo "<pre>";   //debug
//                print_r($data);
//                echo "</pre>";

                $chartrows = '';
                foreach ($data as $datarow) {
                    $chart_rows .= "['" . $datarow->segment_title . "', " . $datarow->record_count . ", ''],";
                }
                $chart_height = count($data) * 25;

                $output = <<<EOF
                    <script type="text/javascript">

                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(drawChart);

                    function drawChart() {
                        var recordsByCollectionsData = google.visualization.arrayToDataTable([
                            ['Item', 'Active Records', { role: "style" }],
EOF;
                $output .= $chart_rows;
                $output .= <<<EOF
                        ]);

                        var recordsByCollectionsView = new google.visualization.DataView(recordsByCollectionsData);
                        recordsByCollectionsView.setColumns([0, 1,
                            { calc: "stringify",
                              sourceColumn: 1,
                              type: "string",
                              role: "annotation" },
                              2]);

                        var recordsByCollectionsOptions = {
                            title: 'Active Grant Records by Collection',
                            chartArea:{width:"50%", height:"100%"},
                            hAxis: {
                                minValue: 0,
                                gridlines: {count: 0}
                            },
                            legend: { position: "none" },
                        };

                        var recordsByCollectionsChart = new google.visualization.BarChart(document.getElementById('recordsByCollections_chart_div'));
                        recordsByCollectionsChart.draw(recordsByCollectionsView, recordsByCollectionsOptions);

                    }
                    </script>

EOF;
                $output .= '<div id="recordsByCollections_chart_div" style="height:' . $chart_height . 'px"></div>';

                break;
            case 'records_last_updated_annually':

                $sql_query = "SELECT
                                  year(g.updated_at) as year, count(g.id) as record_count
                                FROM
                                  " . $wpdb->prefix . "gs_grants as g
                                WHERE
                                    g.status = 'A'
                                GROUP BY
                                    year
                                ORDER BY
                                    year ASC";

//                echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql, "OBJECT" );

//                echo "<pre>";   //debug
//                print_r($data);
//                echo "</pre>";

                $chartrows = '';
                $prev_year = '';
                foreach ($data as $datarow) {
                    if (empty($prev_year)) {
                        $prev_year = $datarow->year;
                    }
                    while (intval($prev_year) < (intval($datarow->year) - 1)) {   //empty year skipped by sql query
                        $chart_rows .= "['" . (intval($prev_year) + 1) . "', 0, ''],";
                        $prev_year = intval($prev_year) + 1;
                    }
                    $chart_rows .= "['" . $datarow->year . "', " . $datarow->record_count . ", ''],";
                    $prev_year = $datarow->year;
                }
                $chart_height = count($data) * 25;

                $output = <<<EOF
                    <script type="text/javascript">

                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(drawChart);

                    function drawChart() {
                        var recordsByYearUpdatedData = google.visualization.arrayToDataTable([
                            ['Year', 'Active Records', { role: "style" }],
EOF;
                $output .= $chart_rows;
                $output .= <<<EOF
                        ]);

                        var recordsByYearUpdatedView = new google.visualization.DataView(recordsByYearUpdatedData);
                        recordsByYearUpdatedView.setColumns([0, 1,
                            { calc: "stringify",
                              sourceColumn: 1,
                              type: "string",
                              role: "annotation" },
                              2]);

                        var recordsByYearUpdatedOptions = {
                            title: 'Active Grant Records by Year Last Updated',
                            chartArea:{width:"50%", height:"100%"},
                            hAxis: {
                                minValue: 0,
                                gridlines: {count: 0}
                            },
                            legend: { position: "none" },
                        };

                        var recordsByYearUpdatedChart = new google.visualization.BarChart(document.getElementById('recordsByYearUpdated_chart_div'));
                        recordsByYearUpdatedChart.draw(recordsByYearUpdatedView, recordsByYearUpdatedOptions);

                    }
                    </script>

EOF;
                $output .= '<div id="recordsByYearUpdated_chart_div" style="height:' . $chart_height . 'px"></div>';

                break;
            case 'records_ready_for_review':

                switch ($format) {
                    case 'count':
                        $sql_select_clause = "count(DISTINCT g.title) as c";
                        break;
                    case 'list':
                        $sql_select_clause = "g.id as grant_id, g.title as grant_title";
                        break;
                }

                $sql_query = "SELECT
                                  $sql_select_clause
                                FROM
                                  " . $wpdb->prefix . "gs_grants as g
                                WHERE
                                    g.status = 'R'
                                ORDER BY
                                    g.updated_at ASC";

//                echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql, "OBJECT" );

//                echo "<pre>";   //debug
//                print_r($data);
//                echo "</pre>";

                switch ($format) {
                    case 'count':
                        $output = number_format( $data[0]->c );
                        break;
                    case 'list':
                        $output = "<p class=\"rfr-records-found\">(" . count($data) . " record";
                        if ( count($data) != 1 ) {
                            $output .= "s";
                        }
                        $output .= " found)</p>";
                        $output .= '<ul>';
//                        if (empty($data)) {
//                            $output .= '<li>(no records found)</li>';
//                        }
                        foreach ( $data as $row_item ) {
                            $output .= '<li><a class="ek-link ek-link" href="/editor/records/edit/?gid=' . $row_item->grant_id . '">' . $row_item->grant_title . '</a></li>';
                        }
                        $output .= "</ul>";
                        break;
                }

                break;
            case 'contributors':
                $exclude_editor_ids = '1,4,903';  // nr5jp, Creative Director, Test Editor
                $sql_query = "SELECT
                                    et.editor_id, users.display_name as editor_name, count(DISTINCT et.grant_id) as qty
                                FROM
                                    " . $wpdb->prefix . "gs_editor_transactions as et
                                LEFT JOIN
                                    " . $wpdb->prefix . "users as users
                                ON
                                    users.id = et.editor_id
                                WHERE
                                     users.id NOT IN (" . $exclude_editor_ids . ")
                                GROUP BY
                                    et.editor_id
                                ORDER BY
                                    et.editor_id";

//        echo "SQLQ: $sql_query<br>";

                $sql = $wpdb->prepare( $sql_query );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql, "OBJECT" );

//        echo "<pre>";   //debug
//        print_r($data);
//        echo "</pre>";

                $res = $data[0]->c;

//        echo "<pre>";   //debug
//        print_r($res);
//        echo "</pre>";
                $output = "<ul>";
                if ( !empty($data) ) {
                    foreach ($data as $key=>$value) {
                        if ( empty($value->editor_name) ) {
                            $value->editor_name = "Unknown User";
                        }
                        $output .= "<li>" . $value->editor_name . " - " . $value->qty . " records edited</li>";
                    }
                }
                $output .= "</ul>";

                break;
            case 'popular_search_terms':

                switch ($period) {
                    case 'past_30_days':
                        $period_str = "the past 30 days";
                        $begin_date = gmdate("Y-m-d H:i:s", strtotime("now -30 days"));
                        $end_date   = gmdate("Y-m-d H:i:s", strtotime("now"));
                        break;
                }

                $sql_query = "SELECT
                                  em.meta_value as keyword, count(em.meta_value) AS count
                              FROM
                                  " . $wpdb->prefix . "gf_entry_meta as em
                              LEFT JOIN
                                  " . $wpdb->prefix . "gf_entry as e
                              ON
                                  em.entry_id = e.id
                              WHERE
                                  ((em.form_id = 1 AND em.meta_key = 1) OR (em.form_id = 2 AND em.meta_key = 1)) AND
                                  (e.date_created >= CAST(%s AS DATETIME) AND e.date_created < CAST(%s AS DATETIME))
                              GROUP BY
                                  em.meta_value
                              ORDER BY
                                  count DESC, e.date_created DESC
                              LIMIT
                                    $limit";

                $sql = $wpdb->prepare( $sql_query, $begin_date, $end_date );

//                echo "SQL: $sql<br>";

                $data = $wpdb->get_results( $sql );

//                echo "<pre>";   //debug
//                print_r($data);
//                echo "</pre>";

                //find min and max values in results
                $min_count=$data[0]->count;
                $max_count=$data[0]->count;
                foreach ($data as $key=>$value) {
                    if ($value->count < $min_count) $min_count = $value->count;
                    if ($value->count > $max_count) $max_count = $value->count;
                }
            //    echo "min: $min_count<br>";
            //    echo "max: $max_count<br>";

                //normalize min/max values
                $count_range = ($max_count - $min_count);
                if ($count_range != 0){
                    foreach ($data as $key=>$value) {
                        $data_normalized[ucfirst($value->keyword)] = 1 + (2 * (($value->count - $min_count) / $count_range));
                    }    
                }else{
                    foreach ($data as $key=>$value) {
                        $data_normalized[ucfirst($value->keyword)] = $value->count;
                    }
                }
                @ksort($data_normalized, SORT_NATURAL | SORT_FLAG_CASE);

//                echo "<pre>";   //debug
//                print_r($data_normalized);
//                echo "</pre>";

                $output = "<ul>";
                if ( empty($data) ) {
                    $output .= '<li>(No keywords searched during ' . $period_str . ')</li>';
                } else {
                    foreach( $data_normalized as $keyword=>$weight_value ) :
                        $output .= '<li style="font-size:' . $weight_value . 'em">' . $keyword . '</li>';
                    endforeach;
                }
                $output .= "</ul>";

                switch ($format) {
                    case 'term_cloud':
                        break;
                }

                break;
        }

        return $output;

    }


    //mass edits pop-up modal
    public static function mass_edits_modal () {
        $content .= '<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">';

        $content .= '<div id="dialog_mass_edits" title="Bulk Edit" style="display:none">';

        $content .= '<p class="intro-text">Choose the field values to update for your selected records (this will override the current values in the selected records)</p>';
        $content .= '<p class="num-affected">0 records will be updated.</p>';

        $ajax_nonce = wp_create_nonce( "shpgs-mass-edits-x8q9z" );

        $content .= '<script type="text/javascript">';
        $content .= <<<_EOT
                        jQuery(document).ready(function () {
                            jQuery( "form.mass-editable" ).submit(function( event ) {
                                //alert( "Form Submitted" );
                                event.preventDefault();

                                var valuesForm = [];
                                var formfields = jQuery(this).serializeArray();
                                jQuery.each(formfields, function(i, field) {
                                    valuesForm[field.name] = field.value;
                                });

                                var subjectOptions = jQuery('select[name="GrantSubjectMappings[subject_title2][]"] option');
                                var valuesSubjects = jQuery.map(subjectOptions, function(option) {
                                    return option.value;
                                });

                                var valuesSegments = jQuery('input[name="GrantSegmentMappings[segment_id][]"]:checked').map(function() {
                                        return jQuery(this).val();
                                    }).get();

                                var valuesRecords = jQuery('table.mass-editable .mass-action-select:checked').map(function() {
                                        return jQuery(this).val();
                                    }).get();

//                                console.log(valuesForm);
//                                console.log(valuesSubjects);
//                                console.log(valuesSegments);
//                                console.log(valuesRecords);

                                // process updates
                                var data = {
                                    'action': 'process_mass_edits',
_EOT;
        $content .= "                        'security': '$ajax_nonce',";
        $content .= <<<_EOT
                                    'addReplaceStatus':     valuesForm['status_add_replace'],
                                    'valueStatus':          valuesForm['grant[status]'],
                                    'addReplaceSubjects':   valuesForm['subj_add_replace'],
                                    'valuesSubjects':       valuesSubjects,
                                    'addReplaceSegments':   valuesForm['seg_add_replace'],
                                    'valuesSegments':       valuesSegments,
                                    'valuesRecords':        valuesRecords
                                };

                                var jqxhr = jQuery.post(searchajax.ajaxurl, data)
                                    .done( function(response) {
                                        alert(response);
                                    });
                            });
                        });
_EOT;
        $content .= '</script>';
        $content .= '<form class="mass-editable" action="" method="post">';

        $content .= '<div id="mass-edits-tabs">';
        $content .= '<ul>';
        $content .= '<li><a href="#mass-edits-status">Status</a></li>';
        $content .= '<li><a href="#mass-edits-subjects">Subject Headings</a></li>';
        $content .= '<li><a href="#mass-edits-segments">Segment (Book) Codes</a></li>';
        $content .= '<li id="mass-edits-process-tab-header"><a href="#mass-edits-process">Process Updates</a></li>';
        $content .= '</ul>';

        $content .= '<div id="mass-edits-status">';
        $content .= '<p><input type="radio" name="status_add_replace" id="status-ar-ignore" value="ignore" /><label for="status-ar-ignore">DO NOT UPDATE existing statuses</label></p>';
        $content .= '<p><input type="radio" name="status_add_replace" id="status-ar-replace" value="replace" /><label for="status-ar-replace">REPLACE existing statuses</label></p>';
        $content .= '<p><select class="grant-status" name="grant[status]" id="grant-status">';
        $content .= '<option value="A">Active</option>';
        $content .= '<option value="P">Pending</option>';
        $content .= '<option value="R">Ready for Review</option>';
        $content .= '<option value="S">Suspended</option>';
        $content .= '</select></p>';
        $content .= '</div>';

        $content .= <<<_EOT

                            <div id="mass-edits-subjects">
                                <p><input type="radio" name="subj_add_replace" id="sub-ar-ignore" value="ignore" /><label for="sub-ar-ignore">DO NOT UPDATE existing subject headings</label></p>
                                <p><input type="radio" name="subj_add_replace" id="sub-ar-replace" value="replace" /><label for="sub-ar-replace">REPLACE existing subject headings with these</label></p>
                                <p><input type="radio" name="subj_add_replace" id="sub-ar-add" value="add" /><label for="sub-ar-add">ADD these to the existing subject headings</p>
                                <p><input type="radio" name="subj_add_replace" id="sub-ar-remove" value="remove" /><label for="sub-ar-remove">REMOVE these from the existing subject headings</p>
                                <p>For multiple selections, hold down <strong>CTRL</strong> (<strong>Command</strong> for Macs) while clicking selections.</p>

                                <select id="GrantSubjectMappings_subject_title" multiple="yes" name="GrantSubjectMappings[subject_title][]" size="20">
_EOT;
        $subjects_list = GrantSelectSearchAddOn::get_subjects_list();
        foreach ( $subjects_list as $key=>$value ) {
            $content .= '<option value="' . $value->id . '">' . stripslashes($value->subject_title) . '</option>';
        }

        $content .= <<<_EOT
                                </select>

                                <input type="button" id="remove" value="<<">
                                <input type="button" id="add" value=">>">

                                <select id="GrantSubjectMappings_subject_title2" multiple="yes" name="GrantSubjectMappings[subject_title2][]" size="20">
_EOT;
        $content .= <<<_EOT
                                </select>

                                <script type="text/javascript">
                                    <!--
                                    var myfilter = new filterlist(document.getElementById('GrantSubjectMappings_subject_title'));
                                    //-->
                                </script>

                                <div class="filter-field">
                                    <p>Filter:
                                        <a title="Clear the filter"href="javascript:myfilter.reset()">Clear</a>
                                        <a title="Show items starting with A" href="javascript:myfilter.set('^A')">A</a>
                                        <a title="Show items starting with B" href="javascript:myfilter.set('^B')">B</a>
                                        <a title="Show items starting with C" href="javascript:myfilter.set('^C')">C</a>
                                        <a title="Show items starting with D" href="javascript:myfilter.set('^D')">D</a>
                                        <a title="Show items starting with E" href="javascript:myfilter.set('^E')">E</a>
                                        <a title="Show items starting with F" href="javascript:myfilter.set('^F')">F</a>
                                        <a title="Show items starting with G" href="javascript:myfilter.set('^G')">G</a>
                                        <a title="Show items starting with H" href="javascript:myfilter.set('^H')">H</a>
                                        <a title="Show items starting with I" href="javascript:myfilter.set('^I')">I</a>
                                        <a title="Show items starting with J" href="javascript:myfilter.set('^J')">J</a>
                                        <a title="Show items starting with K" href="javascript:myfilter.set('^K')">K</a>
                                        <a title="Show items starting with L" href="javascript:myfilter.set('^L')">L</a>
                                        <a title="Show items starting with M" href="javascript:myfilter.set('^M')">M</a>
                                        <a title="Show items starting with N" href="javascript:myfilter.set('^N')">N</a>
                                        <a title="Show items starting with O" href="javascript:myfilter.set('^O')">O</a>
                                        <a title="Show items starting with P" href="javascript:myfilter.set('^P')">P</a>
                                        <a title="Show items starting with Q" href="javascript:myfilter.set('^Q')">Q</a>
                                        <a title="Show items starting with R" href="javascript:myfilter.set('^R')">R</a>
                                        <a title="Show items starting with S" href="javascript:myfilter.set('^S')">S</a>
                                        <a title="Show items starting with T" href="javascript:myfilter.set('^T')">T</a>
                                        <a title="Show items starting with U" href="javascript:myfilter.set('^U')">U</a>
                                        <a title="Show items starting with V" href="javascript:myfilter.set('^V')">V</a>
                                        <a title="Show items starting with W" href="javascript:myfilter.set('^W')">W</a>
                                        <a title="Show items starting with X" href="javascript:myfilter.set('^X')">X</a>
                                        <a title="Show items starting with Y" href="javascript:myfilter.set('^Y')">Y</a>
                                        <a title="Show items starting with Z" href="javascript:myfilter.set('^Z')">Z</a>
                                    </p>
                                    <p>Filter by regular expression:
                                        <input onkeyup="myfilter.set(this.value)" name="regexp"> <input onclick="myfilter.set(this.form.regexp.value)" type="button" value="Filter"> <input onclick="myfilter.reset();this.form.regexp.value=''" type="button" value="Clear"><br>
                                        <input onclick="myfilter.set_ignore_case(!this.checked)" type="checkbox" name="toLowerCase"> Case-sensitive
                                    </p>
                                </div>
                            </div>
_EOT;

        $content .= <<<_EOT
                                <div id="mass-edits-segments">
                                    <p><input type="radio" name="seg_add_replace" id="seg-ar-ignore" value="ignore" /><label for="seg-ar-ignore">DO NOT UPDATE existing segments</label></p>
                                    <p><input type="radio" name="seg_add_replace" id="seg-ar-replace" value="replace" /><label for="seg-ar-replace">REPLACE existing segments with these</label></p>
                                    <p><input type="radio" name="seg_add_replace" id="seg-ar-add" value="add" /><label for="seg-ar-add">ADD these to the existing segments</label></p>
                                    <p><input type="radio" name="seg_add_replace" id="seg-ar-remove" value="remove" /><label for="seg-ar-remove">REMOVE these from the existing segments</label></p>
                                    <p>(check all that apply)</p>
_EOT;
        $y = 1;
        $segment_list = self::get_segment_list();
        foreach ( $segment_list AS $key => $value ){
            if ( $y == 1 ){
                $content .= '<div class="faux_section"><p>';
            }

            if ($value->segment_title == 'Education' || $value->segment_title == 'Scholarships and Fellowships') {
                $content .= '<input type="checkbox" class="grant-segments" id="segment-' . str_replace(" ", "-", $value->segment_title) . '" name="GrantSegmentMappings[segment_id][]" value="' . $value->id . '" /><label for="segment-' . str_replace(" ", "-", $value->segment_title) . '"><strike>' . $value->segment_title . '</strike></label><br />';
            }
            else {
                $content .= '<input type="checkbox" class="grant-segments" id="segment-' . str_replace(" ", "-", $value->segment_title) . '" name="GrantSegmentMappings[segment_id][]" value="' . $value->id . '" /><label for="segment-' . str_replace(" ", "-", $value->segment_title) . '">' . $value->segment_title . '</label><br />';
            }
            if ( $y == 5 ){
                $content .= '</p></div>';
                $y = 0;
            }
            $y++;
        }

        $content .= <<<_EOT
                                </div>
                          </div>
_EOT;

        $content .= '<div id="mass-edits-process">';
        $content .= '<p>The following will be updated in your selected records:</p>';

        $content .= '<p id="status-conf-replace">Status will be updated to:</p>';
        $content .= '<p id="status-conf-list"></p>';
        $content .= '<p id="status-conf-ignore">Status will NOT be updated</p>';

        $content .= '<p id="subj-conf-replace">Existing subject headings will be REPLACED with:</p>';
        $content .= '<p id="subj-conf-add">These subject headings will be ADDED:</p>';
        $content .= '<p id="subj-conf-remove">These subject headings will be REMOVED:</p>';
        $content .= '<p id="subj-conf-list"></p>';
        $content .= '<p id="subj-conf-ignore">Subject headings will NOT be updated</p>';

        $content .= '<p id="seg-conf-replace">Existing segments will be REPLACED with:</p>';
        $content .= '<p id="seg-conf-add">These segments will be ADDED:</p>';
        $content .= '<p id="seg-conf-remove">These segments will be REMOVED:</p>';
        $content .= '<p id="seg-conf-list"></p>';
        $content .= '<p id="seg-conf-ignore">Segments will NOT be updated</p>';

        $content .= '<input type="submit" name="mass_edit_submit" value="Process Updates for Selected Records">';
        $content .= '<p class="warning-text">Warning: This action will update multiple records with your selected edits. PROCEED WITH CAUTION. This action cannot be undone.</p>';
        $content .= '</div>';

        $content .= '</div>';   //mass-edits-tabs
        $content .= '</form>';
        $content .= '</div>';   //dialog_mass_edits

        return $content;
    }


    // process mass edits
    public function process_mass_edits ()
    {
        //security check
        check_ajax_referer( 'shpgs-mass-edits-x8q9z', 'security' );

        if ( !current_user_can('edit_grants') ) {
            echo "Sorry, you're not authorized to edit grants.\n\n";
            die;
        }

        //initialize database connection
        global $wpdb;

        $current_user = get_current_user_id();

//        Editor Capabilities
//        add_gs_records
//        edit_gs_records
//        delete_gs_records

        $add_replace_status     = $_POST['addReplaceStatus'];
        $value_status           = $_POST['valueStatus'];
        $add_replace_subjects   = $_POST['addReplaceSubjects'];
        $values_subjects        = $_POST['valuesSubjects'];
        $add_replace_segments   = $_POST['addReplaceSegments'];
        $values_segments        = $_POST['valuesSegments'];
        $values_records         = filter_var_array ($_POST['valuesRecords'], FILTER_SANITIZE_NUMBER_INT);
        $current_time           = date("Y-m-d H:i:s");
        $error_flag             = false;

        //if add_replace radio button hasn't been selected, be safe and default to 'ignore'
        if ( empty($add_replace_status) ) $add_replace_status = 'ignore';
        if ( empty($add_replace_subjects) ) $add_replace_subjects = 'ignore';
        if ( empty($add_replace_segments) ) $add_replace_segments = 'ignore';

        // if data wasn't submitted, don't update those tables
        if ( empty($value_status) ) $add_replace_status = 'ignore';
        if ( empty($values_subjects) ) $add_replace_subjects = 'ignore';
        if ( empty($values_segments) ) $add_replace_segments = 'ignore';
        if ( empty($values_records) ) {
            echo "Error: No records specified.\n\n";
            die;
        }

//        echo "AR_Status: $add_replace_status\n\n";
//        echo "Status: $value_status\n\n";
//
//        echo "AR_Subjects: $add_replace_subjects\n\n";
//        echo "Subjects:<br>";
//        print_r($values_subjects);
//
//        echo "AR_Segments: $add_replace_segments\n\n";
//        echo "Segments:<br>";
//        print_r($values_segments);
//
//        echo "Records:<br>";
//        print_r($values_records);

        if (
            $add_replace_status == 'ignore' &&
            $add_replace_subjects == 'ignore' &&
            $add_replace_segments == 'ignore'
        ) {
            echo "No records were updated.\n\n";
            die;
        }

        $number_of_records = count( $values_records );
        $list_of_record_ids = implode (",", $values_records);
        $list_of_subject_ids = implode (",", $values_subjects);
        $list_of_segment_ids = implode (",", $values_segments);

        //update status
        if ( $add_replace_status == 'replace' ) {
            $sql_query =   "UPDATE
                                " . $wpdb->prefix . "gs_grants
                            SET
                                status = %s
                            WHERE
                                id IN ( $list_of_record_ids )
                            LIMIT
                                $number_of_records
            ";
            $sql = $wpdb->prepare( $sql_query, $value_status );
//            echo "SQL:$sql \n\n";
            $result_status = $wpdb->get_results( $sql );
            if ($wpdb->last_error) {
                echo "Error: update Status failed.\n";
                $error_flag = true;
            } else {
                echo "Success: Status updated.\n";
            }
        }

        //update subjects
        switch ( $add_replace_subjects ) {
            case "remove":
                //delete any matching subject mappings for the selected records
                $sql_query =   "DELETE FROM
                                    " . $wpdb->prefix . "gs_grant_subject_mappings
                                WHERE
                                    grant_id IN ( $list_of_record_ids ) AND
                                    subject_id IN ( $list_of_subject_ids )
                               ";
                $sql = $wpdb->prepare( $sql_query );
//                echo "SQL:$sql \n\n";
                $result_subjects_remove = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: remove Subjects failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Subjects removed.\n";
                }
                break;
            case "replace":
                //delete all existing subject mappings for the selected records
                $sql_query =   "DELETE FROM
                                    " . $wpdb->prefix . "gs_grant_subject_mappings
                                WHERE
                                    grant_id IN ( $list_of_record_ids )
                               ";   // can't put a limit on this, because there can be multiple mappings per grant_id
                $sql = $wpdb->prepare( $sql_query );
//                echo "SQL:$sql \n\n";
                $result_subjects_delete = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: delete Subjects failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Subjects deleted.\n";
                }
                //no break, because we want the "add" to execute as well
            case "add":
                //insert new subject mappings for the selected records
                $sql_query =   "INSERT INTO
                                    " . $wpdb->prefix . "gs_grant_subject_mappings
                                ( grant_id, subject_id, created_at, updated_at )
                                VALUES ";
                foreach ( $values_records as $record_id ) {
                    foreach ( $values_subjects as $subject_id ) {
                        $sql_query .= "( $record_id, $subject_id, '$current_time', '$current_time' ), ";
                    }
                }
                $sql_query = substr($sql_query, 0, -2);  //remove comma at end
                $sql_query .= " ON DUPLICATE KEY UPDATE updated_at='$current_time'";

                $sql = $wpdb->prepare( $sql_query );
//                echo "SQL:$sql \n\n";
                $result_subjects_add = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: add Subjects failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Subjects added.\n";
                }
                break;
        }

        //update segments
        switch ( $add_replace_segments ) {
            case "remove":
                //delete any matching segment mappings for the selected records
                $sql_query =   "DELETE FROM
                                    " . $wpdb->prefix . "gs_grant_segment_mappings
                                WHERE
                                    grant_id IN ( $list_of_record_ids ) AND
                                    segment_id IN ( $list_of_segment_ids )
                               ";
                $sql = $wpdb->prepare( $sql_query );
//                echo "SQL:$sql \n\n";
                $result_segments_remove = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: remove Segments failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Segments removed.\n";
                }
                break;
            case "replace":
                //delete all existing segment mappings for the selected records
                $sql_query = "DELETE FROM
                                            " . $wpdb->prefix . "gs_grant_segment_mappings
                                        WHERE
                                            grant_id IN ( $list_of_record_ids )
                                       ";   // can't put a limit on this, because there can be multiple mappings per grant_id
                $sql = $wpdb->prepare($sql_query);
//                echo "SQL:$sql \n\n";
                $result_segments_delete = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: delete Segments failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Segments deleted.\n";
                }
            //no break, because we want the "add" to execute as well
            case "add":
                //insert new segment mappings for the selected records
                $sql_query = "INSERT INTO
                                            " . $wpdb->prefix . "gs_grant_segment_mappings
                                        ( grant_id, segment_id, created_at, updated_at )
                                        VALUES ";
                foreach ($values_records as $record_id) {
                    foreach ($values_segments as $segment_id) {
                        $sql_query .= "( $record_id, $segment_id, '$current_time', '$current_time' ), ";
                    }
                }
                $sql_query = substr($sql_query, 0, -2);  //remove comma at end
                $sql_query .= " ON DUPLICATE KEY UPDATE updated_at='$current_time'";

                $sql = $wpdb->prepare($sql_query);
//                echo "SQL:$sql \n\n";
                $result_segments_add = $wpdb->get_results( $sql );
                if ($wpdb->last_error) {
                    echo "Error: add Segments failed.\n";
                    $error_flag = true;
                } else {
                    echo "Success: Segments added.\n";
                }
                break;
        }
        wp_die();
    }

}