<?php
/**
 * Countries helper class for eZZY Wallet.
 *
 * Provides a list of countries for dropdown selections.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_Countries
 *
 * Provides country data for the plugin.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Countries {

    /**
     * Get all countries.
     *
     * @return array Associative array of country codes and names.
     */
    public static function get_countries() {
        return array(
            ''   => __( 'Select Country', 'ezzy-wallet' ),
            'AF' => __( 'Afghanistan', 'ezzy-wallet' ),
            'AL' => __( 'Albania', 'ezzy-wallet' ),
            'DZ' => __( 'Algeria', 'ezzy-wallet' ),
            'AS' => __( 'American Samoa', 'ezzy-wallet' ),
            'AD' => __( 'Andorra', 'ezzy-wallet' ),
            'AO' => __( 'Angola', 'ezzy-wallet' ),
            'AI' => __( 'Anguilla', 'ezzy-wallet' ),
            'AQ' => __( 'Antarctica', 'ezzy-wallet' ),
            'AG' => __( 'Antigua and Barbuda', 'ezzy-wallet' ),
            'AR' => __( 'Argentina', 'ezzy-wallet' ),
            'AM' => __( 'Armenia', 'ezzy-wallet' ),
            'AW' => __( 'Aruba', 'ezzy-wallet' ),
            'AU' => __( 'Australia', 'ezzy-wallet' ),
            'AT' => __( 'Austria', 'ezzy-wallet' ),
            'AZ' => __( 'Azerbaijan', 'ezzy-wallet' ),
            'BS' => __( 'Bahamas', 'ezzy-wallet' ),
            'BH' => __( 'Bahrain', 'ezzy-wallet' ),
            'BD' => __( 'Bangladesh', 'ezzy-wallet' ),
            'BB' => __( 'Barbados', 'ezzy-wallet' ),
            'BY' => __( 'Belarus', 'ezzy-wallet' ),
            'BE' => __( 'Belgium', 'ezzy-wallet' ),
            'BZ' => __( 'Belize', 'ezzy-wallet' ),
            'BJ' => __( 'Benin', 'ezzy-wallet' ),
            'BM' => __( 'Bermuda', 'ezzy-wallet' ),
            'BT' => __( 'Bhutan', 'ezzy-wallet' ),
            'BO' => __( 'Bolivia', 'ezzy-wallet' ),
            'BA' => __( 'Bosnia and Herzegovina', 'ezzy-wallet' ),
            'BW' => __( 'Botswana', 'ezzy-wallet' ),
            'BR' => __( 'Brazil', 'ezzy-wallet' ),
            'BN' => __( 'Brunei', 'ezzy-wallet' ),
            'BG' => __( 'Bulgaria', 'ezzy-wallet' ),
            'BF' => __( 'Burkina Faso', 'ezzy-wallet' ),
            'BI' => __( 'Burundi', 'ezzy-wallet' ),
            'KH' => __( 'Cambodia', 'ezzy-wallet' ),
            'CM' => __( 'Cameroon', 'ezzy-wallet' ),
            'CA' => __( 'Canada', 'ezzy-wallet' ),
            'CV' => __( 'Cape Verde', 'ezzy-wallet' ),
            'KY' => __( 'Cayman Islands', 'ezzy-wallet' ),
            'CF' => __( 'Central African Republic', 'ezzy-wallet' ),
            'TD' => __( 'Chad', 'ezzy-wallet' ),
            'CL' => __( 'Chile', 'ezzy-wallet' ),
            'CN' => __( 'China', 'ezzy-wallet' ),
            'CO' => __( 'Colombia', 'ezzy-wallet' ),
            'KM' => __( 'Comoros', 'ezzy-wallet' ),
            'CG' => __( 'Congo', 'ezzy-wallet' ),
            'CD' => __( 'Congo (DRC)', 'ezzy-wallet' ),
            'CR' => __( 'Costa Rica', 'ezzy-wallet' ),
            'CI' => __( 'Côte d\'Ivoire', 'ezzy-wallet' ),
            'HR' => __( 'Croatia', 'ezzy-wallet' ),
            'CU' => __( 'Cuba', 'ezzy-wallet' ),
            'CY' => __( 'Cyprus', 'ezzy-wallet' ),
            'CZ' => __( 'Czech Republic', 'ezzy-wallet' ),
            'DK' => __( 'Denmark', 'ezzy-wallet' ),
            'DJ' => __( 'Djibouti', 'ezzy-wallet' ),
            'DM' => __( 'Dominica', 'ezzy-wallet' ),
            'DO' => __( 'Dominican Republic', 'ezzy-wallet' ),
            'EC' => __( 'Ecuador', 'ezzy-wallet' ),
            'EG' => __( 'Egypt', 'ezzy-wallet' ),
            'SV' => __( 'El Salvador', 'ezzy-wallet' ),
            'GQ' => __( 'Equatorial Guinea', 'ezzy-wallet' ),
            'ER' => __( 'Eritrea', 'ezzy-wallet' ),
            'EE' => __( 'Estonia', 'ezzy-wallet' ),
            'ET' => __( 'Ethiopia', 'ezzy-wallet' ),
            'FJ' => __( 'Fiji', 'ezzy-wallet' ),
            'FI' => __( 'Finland', 'ezzy-wallet' ),
            'FR' => __( 'France', 'ezzy-wallet' ),
            'GA' => __( 'Gabon', 'ezzy-wallet' ),
            'GM' => __( 'Gambia', 'ezzy-wallet' ),
            'GE' => __( 'Georgia', 'ezzy-wallet' ),
            'DE' => __( 'Germany', 'ezzy-wallet' ),
            'GH' => __( 'Ghana', 'ezzy-wallet' ),
            'GR' => __( 'Greece', 'ezzy-wallet' ),
            'GD' => __( 'Grenada', 'ezzy-wallet' ),
            'GT' => __( 'Guatemala', 'ezzy-wallet' ),
            'GN' => __( 'Guinea', 'ezzy-wallet' ),
            'GW' => __( 'Guinea-Bissau', 'ezzy-wallet' ),
            'GY' => __( 'Guyana', 'ezzy-wallet' ),
            'HT' => __( 'Haiti', 'ezzy-wallet' ),
            'HN' => __( 'Honduras', 'ezzy-wallet' ),
            'HK' => __( 'Hong Kong', 'ezzy-wallet' ),
            'HU' => __( 'Hungary', 'ezzy-wallet' ),
            'IS' => __( 'Iceland', 'ezzy-wallet' ),
            'IN' => __( 'India', 'ezzy-wallet' ),
            'ID' => __( 'Indonesia', 'ezzy-wallet' ),
            'IR' => __( 'Iran', 'ezzy-wallet' ),
            'IQ' => __( 'Iraq', 'ezzy-wallet' ),
            'IE' => __( 'Ireland', 'ezzy-wallet' ),
            'IL' => __( 'Israel', 'ezzy-wallet' ),
            'IT' => __( 'Italy', 'ezzy-wallet' ),
            'JM' => __( 'Jamaica', 'ezzy-wallet' ),
            'JP' => __( 'Japan', 'ezzy-wallet' ),
            'JO' => __( 'Jordan', 'ezzy-wallet' ),
            'KZ' => __( 'Kazakhstan', 'ezzy-wallet' ),
            'KE' => __( 'Kenya', 'ezzy-wallet' ),
            'KI' => __( 'Kiribati', 'ezzy-wallet' ),
            'KP' => __( 'North Korea', 'ezzy-wallet' ),
            'KR' => __( 'South Korea', 'ezzy-wallet' ),
            'KW' => __( 'Kuwait', 'ezzy-wallet' ),
            'KG' => __( 'Kyrgyzstan', 'ezzy-wallet' ),
            'LA' => __( 'Laos', 'ezzy-wallet' ),
            'LV' => __( 'Latvia', 'ezzy-wallet' ),
            'LB' => __( 'Lebanon', 'ezzy-wallet' ),
            'LS' => __( 'Lesotho', 'ezzy-wallet' ),
            'LR' => __( 'Liberia', 'ezzy-wallet' ),
            'LY' => __( 'Libya', 'ezzy-wallet' ),
            'LI' => __( 'Liechtenstein', 'ezzy-wallet' ),
            'LT' => __( 'Lithuania', 'ezzy-wallet' ),
            'LU' => __( 'Luxembourg', 'ezzy-wallet' ),
            'MO' => __( 'Macau', 'ezzy-wallet' ),
            'MK' => __( 'North Macedonia', 'ezzy-wallet' ),
            'MG' => __( 'Madagascar', 'ezzy-wallet' ),
            'MW' => __( 'Malawi', 'ezzy-wallet' ),
            'MY' => __( 'Malaysia', 'ezzy-wallet' ),
            'MV' => __( 'Maldives', 'ezzy-wallet' ),
            'ML' => __( 'Mali', 'ezzy-wallet' ),
            'MT' => __( 'Malta', 'ezzy-wallet' ),
            'MH' => __( 'Marshall Islands', 'ezzy-wallet' ),
            'MR' => __( 'Mauritania', 'ezzy-wallet' ),
            'MU' => __( 'Mauritius', 'ezzy-wallet' ),
            'MX' => __( 'Mexico', 'ezzy-wallet' ),
            'FM' => __( 'Micronesia', 'ezzy-wallet' ),
            'MD' => __( 'Moldova', 'ezzy-wallet' ),
            'MC' => __( 'Monaco', 'ezzy-wallet' ),
            'MN' => __( 'Mongolia', 'ezzy-wallet' ),
            'ME' => __( 'Montenegro', 'ezzy-wallet' ),
            'MA' => __( 'Morocco', 'ezzy-wallet' ),
            'MZ' => __( 'Mozambique', 'ezzy-wallet' ),
            'MM' => __( 'Myanmar', 'ezzy-wallet' ),
            'NA' => __( 'Namibia', 'ezzy-wallet' ),
            'NR' => __( 'Nauru', 'ezzy-wallet' ),
            'NP' => __( 'Nepal', 'ezzy-wallet' ),
            'NL' => __( 'Netherlands', 'ezzy-wallet' ),
            'NZ' => __( 'New Zealand', 'ezzy-wallet' ),
            'NI' => __( 'Nicaragua', 'ezzy-wallet' ),
            'NE' => __( 'Niger', 'ezzy-wallet' ),
            'NG' => __( 'Nigeria', 'ezzy-wallet' ),
            'NO' => __( 'Norway', 'ezzy-wallet' ),
            'OM' => __( 'Oman', 'ezzy-wallet' ),
            'PK' => __( 'Pakistan', 'ezzy-wallet' ),
            'PW' => __( 'Palau', 'ezzy-wallet' ),
            'PS' => __( 'Palestine', 'ezzy-wallet' ),
            'PA' => __( 'Panama', 'ezzy-wallet' ),
            'PG' => __( 'Papua New Guinea', 'ezzy-wallet' ),
            'PY' => __( 'Paraguay', 'ezzy-wallet' ),
            'PE' => __( 'Peru', 'ezzy-wallet' ),
            'PH' => __( 'Philippines', 'ezzy-wallet' ),
            'PL' => __( 'Poland', 'ezzy-wallet' ),
            'PT' => __( 'Portugal', 'ezzy-wallet' ),
            'PR' => __( 'Puerto Rico', 'ezzy-wallet' ),
            'QA' => __( 'Qatar', 'ezzy-wallet' ),
            'RO' => __( 'Romania', 'ezzy-wallet' ),
            'RU' => __( 'Russia', 'ezzy-wallet' ),
            'RW' => __( 'Rwanda', 'ezzy-wallet' ),
            'KN' => __( 'Saint Kitts and Nevis', 'ezzy-wallet' ),
            'LC' => __( 'Saint Lucia', 'ezzy-wallet' ),
            'VC' => __( 'Saint Vincent and the Grenadines', 'ezzy-wallet' ),
            'WS' => __( 'Samoa', 'ezzy-wallet' ),
            'SM' => __( 'San Marino', 'ezzy-wallet' ),
            'ST' => __( 'São Tomé and Príncipe', 'ezzy-wallet' ),
            'SA' => __( 'Saudi Arabia', 'ezzy-wallet' ),
            'SN' => __( 'Senegal', 'ezzy-wallet' ),
            'RS' => __( 'Serbia', 'ezzy-wallet' ),
            'SC' => __( 'Seychelles', 'ezzy-wallet' ),
            'SL' => __( 'Sierra Leone', 'ezzy-wallet' ),
            'SG' => __( 'Singapore', 'ezzy-wallet' ),
            'SK' => __( 'Slovakia', 'ezzy-wallet' ),
            'SI' => __( 'Slovenia', 'ezzy-wallet' ),
            'SB' => __( 'Solomon Islands', 'ezzy-wallet' ),
            'SO' => __( 'Somalia', 'ezzy-wallet' ),
            'ZA' => __( 'South Africa', 'ezzy-wallet' ),
            'SS' => __( 'South Sudan', 'ezzy-wallet' ),
            'ES' => __( 'Spain', 'ezzy-wallet' ),
            'LK' => __( 'Sri Lanka', 'ezzy-wallet' ),
            'SD' => __( 'Sudan', 'ezzy-wallet' ),
            'SR' => __( 'Suriname', 'ezzy-wallet' ),
            'SZ' => __( 'Eswatini', 'ezzy-wallet' ),
            'SE' => __( 'Sweden', 'ezzy-wallet' ),
            'CH' => __( 'Switzerland', 'ezzy-wallet' ),
            'SY' => __( 'Syria', 'ezzy-wallet' ),
            'TW' => __( 'Taiwan', 'ezzy-wallet' ),
            'TJ' => __( 'Tajikistan', 'ezzy-wallet' ),
            'TZ' => __( 'Tanzania', 'ezzy-wallet' ),
            'TH' => __( 'Thailand', 'ezzy-wallet' ),
            'TL' => __( 'Timor-Leste', 'ezzy-wallet' ),
            'TG' => __( 'Togo', 'ezzy-wallet' ),
            'TO' => __( 'Tonga', 'ezzy-wallet' ),
            'TT' => __( 'Trinidad and Tobago', 'ezzy-wallet' ),
            'TN' => __( 'Tunisia', 'ezzy-wallet' ),
            'TR' => __( 'Turkey', 'ezzy-wallet' ),
            'TM' => __( 'Turkmenistan', 'ezzy-wallet' ),
            'TV' => __( 'Tuvalu', 'ezzy-wallet' ),
            'UG' => __( 'Uganda', 'ezzy-wallet' ),
            'UA' => __( 'Ukraine', 'ezzy-wallet' ),
            'AE' => __( 'United Arab Emirates', 'ezzy-wallet' ),
            'GB' => __( 'United Kingdom', 'ezzy-wallet' ),
            'US' => __( 'United States', 'ezzy-wallet' ),
            'UY' => __( 'Uruguay', 'ezzy-wallet' ),
            'UZ' => __( 'Uzbekistan', 'ezzy-wallet' ),
            'VU' => __( 'Vanuatu', 'ezzy-wallet' ),
            'VA' => __( 'Vatican City', 'ezzy-wallet' ),
            'VE' => __( 'Venezuela', 'ezzy-wallet' ),
            'VN' => __( 'Vietnam', 'ezzy-wallet' ),
            'YE' => __( 'Yemen', 'ezzy-wallet' ),
            'ZM' => __( 'Zambia', 'ezzy-wallet' ),
            'ZW' => __( 'Zimbabwe', 'ezzy-wallet' ),
        );
    }

    /**
     * Get country name by code.
     *
     * @param string $code Country code.
     * @return string Country name or empty string.
     */
    public static function get_country_name( $code ) {
        $countries = self::get_countries();
        return isset( $countries[ $code ] ) ? $countries[ $code ] : '';
    }

    /**
     * Render country dropdown options.
     *
     * @param string $selected Selected country code.
     * @return void
     */
    public static function render_options( $selected = '' ) {
        $countries = self::get_countries();
        foreach ( $countries as $code => $name ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $code ),
                selected( $selected, $code, false ),
                esc_html( $name )
            );
        }
    }
}
