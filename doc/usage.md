# Using the SDK client

Once you have a client [set up and running](installation.md), you can use it to
query the API. Currently there are two methods you can call to retrieve data:

## OneCart\Api\Client::allStocks()

This method fetches all available stocks and returns an iterable `Generator` object,
yielding an instance of `OneCart\Api\Model\ProductStock` on each iteration.

## OneCart\Api\Client::allProducts()

This method fetches all available products and returns an iterable `Generator` object,
yielding an instance of `OneCart\Api\Model\Product` on each iteration.

### Accessing product price

`OneCart\Api\Model\Product::getPrice()` returns an instance of `OneCart\Api\Model\ProductPrice`.
It can be converted directly to a formatted string by simple type-casting, but
should you wish to use it, you can access a `Money\Money` object representation
through the `OneCart\Api\Model\ProductPrice::asMoneyObject()` method. Read more
in the official [moneyphp/money](http://moneyphp.org/en/stable) library on how
to use it.
