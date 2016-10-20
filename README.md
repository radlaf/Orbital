### Orbital

Implementation of Authorize and Mark for Capture using the Orbital Gateway XML Interface v5.8

[Chase Paymentech] (http://download.chasepaymentech.com/ "Chase Paymentech")

### Usage
```
<?php
  require_once 'Orbital.php'

  try {
    $request = new Orbital(
      $username,
      $password,
      $industry_type,
      $bin,
      $merchant_id,
      $terminal_id
    );

    $response = $request->authorize(
      array(
        'OrderID'           => time(),
        'Amount'            => '100',
        'AccountNum'        => '5454545454545454',
        'Exp'               => '0918',
        'CardSecVal'        => '111',
        'CurrencyCode'      => '840',
        'CurrencyExponent'  => '2'
      )
    );
    print_r($response);
  } catch (Exception $e) {
    echo $e->getMessage();
  }
?>
```
