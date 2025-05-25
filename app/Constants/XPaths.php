<?php 

namespace App\Constants;

class XPaths
{
    public const LINKS = "//div[@id='js-results']//li/div/a";
    public const TITLE = "//meta[@property='og:title']";
    public const AUTHORS = "//div[contains(@class, 'gem-c-metadata')]//a[contains(@class, 'govuk-link')]";
}