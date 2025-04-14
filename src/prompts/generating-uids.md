# How to Generate UUIDs (aka UIDs)

UUIDs (Universally Unique Identifiers), also known as UIDs, are used to uniquely identify many objects within the Craft CMS system. Ensuring that they are used consistently is critical.

If an element (ie: field) is being assigned somewhere (ie: a field layout), it MUST use the correct UUID to properly identify itself.

## Formula for a UUID:

Here is the PHP logic for generating a new UUID:

```php
$UUID = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

    // 32 bits for "time_low"
    random_int(0, 0xffff), random_int(0, 0xffff),

    // 16 bits for "time_mid"
    random_int(0, 0xffff),

    // 16 bits for "time_hi_and_version", four most significant bits holds version number 4
    random_int(0, 0x0fff) | 0x4000,

    // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and
    // one for variant DCE1.1
    random_int(0, 0x3fff) | 0x8000,

    // 48 bits for "node"
    random_int(0, 0xffff), random_int(0, 0xffff), random_int(0, 0xffff)
);
```

When generating a new UUID, use the precise logic shown above.
