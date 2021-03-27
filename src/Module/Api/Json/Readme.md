# Experimental json api

## Encoding

The body has to be json encoded, the result is also json encoded.

## Results and errors

Every result contains a leading `data` key to distinguish valid results from error results.

```json
{
  "data": {
    "some-result" => "some-value"
  }
}
```

Errors can therefor be identified by looking for the leading `errors` key.

```json
{
  "error": {
    "code": 666,
    "message": "Some error message"
  }
}
```

Every type of error has it's own error code. They are defined in `\Ampache\Module\Api\Json\ErrorHandling\ErrorCodeEnum`.

In certain cases (internal server errors, authentication failures, ...) the api will respond with corresponding http status codes.
In those cases, the result will be empty.

## Auth

The api works by using jwt (json web token) for authentication.
A jwt could be obtained by calling one of the available lotgin methods in the session namespace.

The generated jwt needs to be send as an authorization header with every request.
`Authorization: Bearer <jwt>` (without `<>`). The expiration date as well as other options
can be configured in the ampache config file.

## Methods V1

### Session

#### POST /v1/session/login

Performs a login using a users credentials and returns a jwt

##### Body

| Parameter | Type   |     |
| --------- | ------ | --- |
| username  | string | required |
| password  | string | required |

##### Result

```json
{
  "data": {
    "jwt": "some-token"
  }
}
```


### Podcast

#### GET /v1/podcast

Retrieve podcast item ids.

##### Query

| Parameter  | Type   | Description |     |
| ---------  | ------ | --- | --- |
| sortField  | string | Fieldname for sorting | optional |
| sortOrder  | string | Order for sorting (ASC/DESC) | optional |
| limit      | int    | Limit result to n items | optional |
| offset     | int    | Begin at offset n | optional |

##### Result

```json
{
  "data": [
    {
      "id": 5,
      "cacheKey": null // ?int; contains a cache key if available
    },
    {
      "id": 3,
      "cacheKey": 12345
    },
    {
      "id": 4,
      "cacheKey": null
    }
  ]
}
```

#### GET /v1/podcast/<podcast-id>

Retrieve the details of a certain podcast.

##### Result

```json
{
  "data": {
    "title": "radioWissen",
    "description": "radioWissen, ein sinnliches H&ouml;rerlebnis mit Anspruch: Spannendes aus der Geschichte, Interessantes aus Literatur und Musik, Faszinierendes &uuml;ber Mythen, Menschen und Religionen, Erhellendes aus Philosophie und Psychologie. Wissenswertes &uuml;ber Natur, Biologie und Umwelt, Hintergr&uuml;nde zu Wirtschaft und Politik. Die ganze Welt des Wissens - gut recherchiert, spannend erz&auml;hlt. Bildung mit Unterhaltungswert.",
    "language": "de-DE",
    "copyright": "",
    "feedUrl": "https://feeds.br.de/radiowissen/feed.xml",
    "generator": "BR Podcasts",
    "website": "https://www.br.de/mediathek/podcast/radiowissen/488",
    "buildDate": 1615552385, // date of the last podcast xml build
    "syncDate": 1616403614, // date of the last sync in ampache
    "publicUrl": "https://web.local/podcast.php?action=show&podcast=3"
  }
}
```
