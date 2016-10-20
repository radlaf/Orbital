<?php
/**
 * Implementation of Authorize and Mark for Capture using the Orbital Gateway XML Interface v5.8.
 *
 * Example:
 * <code>
 * <?php
 * require_once 'Orbital.php'
 * try {
 *   $request = new Orbital($username, $password, $industry_type, $bin, $merchant_id, $terminal_id);
 *   $response = $request->authorize(
 *     array(
 * 	    'OrderID'           => time(),
 * 	    'Amount'            => '100',
 * 	    'AccountNum'        => '5454545454545454',
 * 	    'Exp'               => '0918',
 * 	    'CardSecVal'        => '111',
 * 	    'CurrencyCode'      => '840',
 * 	    'CurrencyExponent'  => '2'
 *     )
 *   );
 *   print_r($response);
 * } catch (Exception $e) {
 *   echo $e->getMessage();
 * }
 * ?>
 * </code>
 *
 * @package    Orbital
 * @subpackage Orbital
 * @link       http://download.chasepaymentech.com/
 */

/**
 * Generates Orbital request and parses the response
 *
 * @package    Orbital
 * @subpackage Orbital
 */
class Orbital
{
    const PRIMARY_CERTIFICATION_URL   = "https://orbitalvar1.paymentech.net/authorize";
    const SECONDARY_CERTIFICATION_URL = "https://orbitalvar2.paymentech.net/authorize";

    const PRIMARY_PRODUCTION_URL      = "https://orbital1.paymentech.net/authorize";
    const SECONDARY_PRODUCTION_URL    = "https://orbital2.paymentech.net/authorize";

    const XSD_REQUEST                 = "xsd/Request_PTI58.xsd";
    const XSD_RESPONSE                = "xsd/Response_PTI58.xsd";

    const NEW_ORDER                   = "NewOrder";
    const MARK_FOR_CAPTURE            = "MarkForCapture";

    const AUTHORIZE                   = "A";
    const AUTHORIZE_MARK_FOR_CAPTURE  = "AC";
    const FORCE_CAPTURE               = "FC";
    const REFUND                      = "R";

    protected $config;
    protected $request;
    protected $response;

    private $production = FALSE;

    private $orbital_connection_username;
    private $orbital_connection_password;
    private $industry_type;
    private $bin;
    private $merchant_id;
    private $terminal_id;

    private $new_order_fields = array(
        "OrbitalConnectionUsername","OrbitalConnectionPassword","IndustryType","MessageType","BIN","MerchantID","TerminalID",
        "CardBrand","AccountNum","Exp","CurrencyCode","CurrencyExponent","CardSecValInd","CardSecVal","DebitCardIssueNum",
        "DebitCardStartDate","BCRtNum","CheckDDA","BankAccountType","ECPAuthMethod","BankPmtDelv","AVSzip","AVSaddress1",
        "AVSaddress2","AVScity","AVSstate","AVSphoneNum","AVSname","AVScountryCode","AVSDestzip","AVSDestaddress1",
        "AVSDestaddress2","AVSDestcity","AVSDeststate","AVSDestphoneNum","AVSDestname","AVSDestcountryCode",
        "CustomerProfileFromOrderInd","CustomerRefNum","CustomerProfileOrderOverrideInd","Status","AuthenticationECIInd",
        "CAVV","XID","PriorAuthID","OrderID","Amount","Comments","ShippingRef","TaxInd","Tax","AMEXTranAdvAddn1",
        "AMEXTranAdvAddn2","AMEXTranAdvAddn3","AMEXTranAdvAddn4","AAV","SDMerchantName","SDProductDescription",
        "SDMerchantCity","SDMerchantPhone","SDMerchantURL","SDMerchantEmail","RecurringInd","EUDDCountryCode",
        "EUDDBankSortCode","EUDDRibCode","BMLCustomerIP","BMLCustomerEmail","BMLShippingCost","BMLTNCVersion",
        "BMLCustomerRegistrationDate","BMLCustomerTypeFlag","BMLItemCategory","BMLPreapprovalInvitationNum",
        "BMLMerchantPromotionalCode","BMLCustomerBirthDate","BMLCustomerSSN","BMLCustomerAnnualIncome",
        "BMLCustomerResidenceStatus","BMLCustomerCheckingAccount","BMLCustomerSavingsAccount",
        "BMLProductDeliveryType","BillerReferenceNumber","MBType","MBOrderIdGenerationMethod",
        "MBRecurringStartDate","MBRecurringEndDate","MBRecurringNoEndDateFlag","MBRecurringMaxBillings",
        "MBRecurringFrequency","MBDeferredBillDate","MBMicroPaymentMaxDollarValue","MBMicroPaymentMaxBillingDays",
        "MBMicroPaymentMaxTransactions","TxRefNum","PCOrderNum","PCDestZip","PCDestName","PCDestAddress1",
        "PCDestAddress2","PCDestCity","PCDestState","PC3FreightAmt","PC3DutyAmt","PC3DestCountryCd","PC3ShipFromZip",
        "PC3DiscAmt","PC3VATtaxAmt","PC3VATtaxRate","PC3AltTaxInd","PC3AltTaxAmt","PC3LineItemCount","PC3LineItemArray",
        "PartialAuthInd","AccountUpdaterEligibility","UseStoredAAVInd","ECPActionCode","ECPCheckSerialNumber",
        "ECPTerminalCity","ECPTerminalState","ECPImageReferenceNumber","CustomerAni","AVSPhoneType","AVSDestPhoneType",
        "CustomerEmail","CustomerIpAddress","EmailAddressSubtype","CustomerBrowserName","ShippingMethod","FraudAnalysis",
        "SoftMerchantDescriptors","CardIndicators"
    );

    private $mark_for_capture_fields = array(
        "OrbitalConnectionUsername","OrbitalConnectionPassword","OrderID","Amount","TaxInd","Tax","BIN","MerchantID","TerminalID","TxRefNum","PCOrderNum","PCDestZip","PCDestName",
        "PCDestAddress1","PCDestAddress2","PCDestCity","PCDestState","AMEXTranAdvAddn1","AMEXTranAdvAddn2","AMEXTranAdvAddn3","AMEXTranAdvAddn4","PC3FreightAmt","PC3DutyAmt",
        "PC3DestCountryCd","PC3ShipFromZip","PC3DiscAmt","PC3VATtaxAmt","PC3VATtaxRate","PC3AltTaxInd","PC3AltTaxID","PC3AltTaxAmt","PC3LineItemCount","PC3LineItemArray"
    );

    /**
     * Constructor.
     *
     * @param string $orbital_connection_username       API username
     * @param string $orbital_connection_password       API password
     * @param string $industry_type                     Industry type
     * @param string $bin                               Transaction routing
     * @param string $merchant_id                       Merchant ID
     * @param string $terminal_id                       Salem or PNS
     */
    public function __construct($orbital_connection_username = FALSE, $orbital_connection_password = FALSE, $industry_type = FALSE, $bin = FALSE, $merchant_id = FALSE, $terminal_id = FALSE)
    {
        $this->orbital_connection_username  = $orbital_connection_username;
        $this->orbital_connection_password  = $orbital_connection_password;
        $this->industry_type                = $industry_type;
        $this->bin                          = $bin;
        $this->merchant_id                  = $merchant_id;
        $this->terminal_id                  = $terminal_id;
    }

    /**
     * Operate in test mode or production
     *
     * @param bool $production Testing or live
     *
     * @return Orbital self
     */
    public function setProduction($production = FALSE)
    {
        $this->production = $production;
        return $this;
    }

    /**
     * Return test mode status
     *
     * @return bool
     */
    public function getProduction()
    {
        return $this->production;
    }

    /**
     * Authorize a transaction
     *
     * @param array $transaction Transaction paramters
     *
     * @return array Response data
     */
    public function authorize(array $transaction)
    {
        $transaction['MessageType'] = self::AUTHORIZE;
        $response = $this->_generateRequest(self::NEW_ORDER, $transaction);
        return $this->_parseResponse($response);
    }

    /**
     * Capture an authorized transaction
     *
     * @param array $transaction Transaction paramters
     *
     * @return array Response data
     */
    public function markForCapture(array $transaction)
    {
        $response = $this->_generateRequest(self::MARK_FOR_CAPTURE, $transaction);
        return $this->_parseResponse($response);
    }

    /**
     * Generate and send a request to the Orbital Gateway
     *
     * @return string The response.
     */
    protected function _generateRequest($transaction_type, $transaction)
    {
        // Build  and Validate XML
        if ($this->_createXml($transaction_type, $transaction)) {
            return $this->_sendRequest();
        } else {
            throw new Exception("Invalid Orbital Request Schema");
        }
    }

    /**
     * Build SimpleXMLElement to be posted
     *
     * @param string $transaction_type  The action to be performed
     * @param array  $transaction       The transaction
     *
     * @return bool True if the request is valid
     */
    protected function _createXml($transaction_type, array $transaction)
    {
        // Build params array
        $params = $this->_buildParams($transaction);

        // Create XML
        $this->request = new DOMDocument('1.0', 'utf-8');
        $this->request->formatOutput = $this->getProduction() ? FALSE : TRUE;

        $request_element = $this->request->createElement('Request');
        $request_node = $this->request->appendChild($request_element);

        $parent_element = $this->request->createElement($transaction_type);
        $parent_node = $request_node->appendChild($parent_element);

        // Add fields
        $fields = $this->{strtolower(preg_replace('/\B([A-Z])/', '_$1', $transaction_type)) . '_fields'};
        foreach ($fields as $field) {
            if (in_array($field, array_keys($params))) {
                $item = $this->request->createElement($field, $params[$field]);
                $parent_node->appendChild($item);
            }
        }

        return $this->request->schemaValidate(__DIR__ . '/' .self::XSD_REQUEST);
    }

    /**
     * Helper that builds the transaction array prior to the request
     *
     * @return array The transaction array
     */
    protected function _buildParams($transaction)
    {
        $params = array(
            'OrbitalConnectionUsername' => $this->orbital_connection_username,
            'OrbitalConnectionPassword' => $this->orbital_connection_password,
            'IndustryType'              => $this->industry_type,
            'BIN'                       => $this->bin,
            'MerchantID'                => $this->merchant_id,
            'TerminalID'                => $this->terminal_id
        );
        return array_merge($transaction, $params);
    }

    /**
     * Posts the request to Chase and returns response.
     *
     * @return array The response
     */
    protected function _sendRequest()
    {
        $post_url = $this->getProduction() ? self::PRIMARY_PRODUCTION_URL : self::PRIMARY_CERTIFICATION_URL;
        $post = $this->request->saveXML();

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: application/PTI58',
            'Content-length: '.strlen($post),
            'Content-transfer-encoding: text',
            'Request-number: 1',
            'Document-type: Request',
        );

        $curl_request = curl_init($post_url);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl_request, CURLOPT_HEADER, FALSE);
        curl_setopt($curl_request, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl_request, CURLOPT_POST, TRUE);
        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $post);
        $this->response = curl_exec($curl_request);

        return $this->response;
    }

    /**
     * Parse the response and check for success
     *
     * @param string $response The response XML
     *
     * @return array The successful response
     */
    protected function _parseResponse($response) {
        // Validate response and check for errors
        $dom = new DOMDocument();
        $dom->formatOutput = $this->getProduction() ? FALSE : TRUE;
        $dom->loadXML($response);

        if ($dom->schemaValidate(__DIR__ . '/' .self::XSD_RESPONSE)) {
            $parent = $dom->getElementsByTagName('Response');
            $children = $parent->item(0)->childNodes;
            $transaction = $children->item(0);
            $transaction_results = array();
            foreach ($transaction->childNodes as $item) {
                $transaction_results[$item->nodeName] = $item->nodeValue;
            }
            switch ($transaction->nodeName) {
                case 'NewOrderResp':
                case 'MarkForCaptureResp':
                    if ($transaction_results['ProcStatus'] == 0) {
                        return $transaction_results;
                    } else {
                        throw new Exception($transaction_results['StatusMsg']);
                    }
                    break;
            }
            return;
        } else {
            throw new Exception("Invalid Orbital Response Schema");
        }
    }
}