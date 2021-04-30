## Payflexi Flexible Installment Payment Plans for Magento v2.3.0 - 2.3.5

## Description

**PayFlexi Flexible Payment & Fast Checkout plugin for Magento23 is a payment option that allows you to accept layaway, installment or one-time payments from your customers before getting access to the product**. 

### Use Case for PayFlexi 

The opportunity to split the payment into several parts can increase the number of orders and facilitate the conversion of doubting customers, especially if you are selling an high value products. 
PayFlexi allows your customers to buy products just by paying down payment at the time of purchase and remaining amount to be paid later in easy installments. Here are some benefits;

* Sell your high-value items at the right price without looking expensive.
* Start accepting down payment for products ahead of launch (pre-order).
* Increase the average order and motivate your customers to pay for more high-value items.
* Builds a trustworthy relationship between your business and the customers.
* Create an easy payment experience for your customers who cannot pay the full amount beforehand to prevent losing their orders.
* Immediate cash flow

### Features

* Accept one-time payment or installment payment from your customers.
* Let customers customize plans within the limits that you set.
* Set the minimum amount to enable for installment payment.
* Set the minimum amount to enable for weekly or monthly installment payment.
* Accept payments via your existing payment processor and get paid instantly.
* Manage and view all payment schedules from your dedicated merchant dashboard.
* Customers have access to dedicated dashboard for managing their payment schedules.

With PayFlexi, you can bring your existing payment processor to accept payment. We currently support;

* __Stripe__
* __PayStack__
* __Flutterwave__

* To signup for a PayFlexi Merchant account visit their website by clicking [here](https://merchant.payflexi.co)

New Payment Gateways will be added regularly. If there is a Payment Gateway that you need urgently or a feature missing that you think we must add, [get in touch with us](https://payflexi.co/contact/) and we will consider it.


## Installation

### Magento Connect

Coming Soon.

### Composer

Coming Soon.

### Manual Installation

*  Click this [link](https://www.dropbox.com/s/7em5v6mry5pddcu/payflexi-magento-23.zip?dl=0) to Download Zip and save to your local machine.
*  Unpack(Extract) the archive.
*  Copy the content of the __payflexi-magento-23__ directory into your Magento's __app/code__ directory.
*  Enable the PayFlexi Payments module:
   From your commandline, in your magento root directory, run
   ```bash
    php bin/magento module:enable Payflexi_Checkout --clear-static-content
    php bin/magento setup:upgrade
    php bin/magento setup:di:compile
  ```

### Configure the plugin

Configuration can be done using the Administrator section of your Magento store.

* From the admin dashboard, using the left menu navigate to __Stores__ > __Configuration__ > __Sales__ > __Payment Methods__.
* Select __PayFlexi Payments__ from the list of recommended modules.
* Set __Enable__ to __Yes__ and fill the rest of the config form accordingly, then click the orange __Save Config__ to save and activate.

### Suggestions / Contributions

For issues and feature request, [click here](https://github.com/PayFlexiHQ/plugin-magento-2/issues).
To contribute, fork the repo, add your changes and modifications then create a pull request.



