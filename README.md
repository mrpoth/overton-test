# Tech test for Overton
The purpose of this repo is to cover the three tasks set out by the [Overton PHP tech test](https://github.com/overtonpolicy/Overton-PHP-code-test).

To set up this repo, run the following commands after cloning the repo in your local install. From the main directory:

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
composer run dev
```

After that, you can either visit your [Overton PHP tech test](http://localhost:8000/) or run the command ``php artisan parser:links``

Below follow the requirements for Step 3, a rudimentary design:

In its most basic, the requirements can be filled mostly if not fully within Laravel's own ecosystem, specifically its queueing system.

Using custom jobs and an interface, you could set up all of the above requirements such as delaying calls for X number of seconds, as well as keeping track of how many times each IP has sent a request.

The cache system could also be utilised in the event of a 429 response code, so that you could keep track of which sites should be left alone for the moment with a timestamp.

This would be a good candidate for job batching as well. Even though Laravel does have some first party-tooling to monitor all these, such as Horizon, I've found that they are not often enough to debug issues, so additional logging would be required.

So, in essence, I would:

Create an interface (DomainParserRequirementsInterface) with a bunch of methods that would define things like delays, etc, then create classe that would implement those.

Then, some sort of ProxyManager class that would keep track of which proxies to use for which domain (if that's the desired behaviour) or a randomised along with a tracker so that all are utilised evenly.

After that, I'd set up jobs that would take each batch of new pages and pass them down to each domain handler to deal with. 