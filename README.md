# Summary

This repository contains code tweaks for the Wordpress website https://dientesdeleche.com

The site uses a customised child theme derived from [hello-elementor](https://elementor.com/hello-theme/).

## WooCommerce
Changes made in the WooCommerce plugin for Wordpress:

- A new email type was added for pending orders.
- Code was updated to allow receiving both Paypal IPN and PDT notifications. This will improve the user experience when buying digital products as they can immediately see the download links after a PDT is sent or still get the email notification by using IPN in case that PDT fails.

### Tweaks in functions.php

- Replace "Add to Cart" button for "View Product" (Ver Producto) in the shop.
- Skip Cart and show "Buy Now" (Comprar Ahora) button instead.
- Remove some checkout fields.
- Add navigation arrows in product gallery.
- Set auto slide for carrousel.
- Force to send email when order is cancelled.
- Send new pending order email when an order is created.
- Limit amount of products per page.

Original plugin source: https://wordpress.org/plugins/woocommerce/
