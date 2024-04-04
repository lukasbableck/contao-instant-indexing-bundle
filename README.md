# contao-instant-indexing-bundle (WIP)
This bundle allows to send a crawl request to the Google Indexing API for newly published pages, news, calendar events and faqs either automatically or manually.
The API is intended for pages that contain JobPosting or BroadcastEvent structured data but it actually works for any page.
This is useful for sites that publish time-sensitive content and want to have it indexed as soon as possible.

## Installation
```bash
composer require lukasbableck/contao-instant-indexing-bundle
```

## Configuration
1. Create a new project in the (Google Cloud Console)[https://console.cloud.google.com/apis/enableflow?apiid=indexing.googleapis.com&credential=client_key]
2. Enable the Google Indexing API
3. Create a new service account and a new private key (JSON) for the account
4. Add the service account email to the Google Search Console with the "Owner" role
5. Paste the contents of the private key file into the 'Google Service Account JSON' field in the root page settings

## Usage
If enabled in the root page settings, the bundle will send a crawl request to the Google Indexing API for newly published pages, news, calendar events and faqs automatically.
You can also manually send a crawl request by clicking the "Send crawl request" button in the page list.
Deleted pages can also be removed from the index automatically if the option is enabled in the root page settings.