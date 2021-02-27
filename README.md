## Payflexi Flexible Installment Payment Plans for Magento v2.3.0 - 2.3.5

This module would allow your customers to pay for products in flexible installment using your existing payment processors. We currently integrate with Stripe, PayStack and Flutterwave

## Install

* Go to Magento2 root folder

* Enter following command to install module:

```bash
composer require payflexi/payflexi-magento2-module
```

* Wait while dependencies are updated.

* Enter following commands to enable module:

```bash
php bin/magento module:enable Payflexi_Checkout --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
```

