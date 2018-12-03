# Using the SDK client

Once you have a client [set up and running](installation.md), you can use it to
query the API. Currently there are two methods you can call to retrieve data:

## OneCart\Api\Client::allStocks()

This method fetches all available stocks and returns an iterable `Generator` object,
yielding an instance of `OneCart\Api\Model\ProductStock` on each iteration.

## OneCart\Api\Client::allProducts()

This method fetches all available products and returns an iterable `Generator` object,
yielding an instance of `OneCart\Api\Model\Product` on each iteration.
