# Use JavaCC instead

[click me](https://javacc.github.io/javacc/)

### Install
```
composer require yufangcheng/search-parser
```

Add the provider below to `config/app.app`
```
Inno\Lib\SearchParser\Providers\SearchParserServiceProvider::class
```

Then run the command
```
php artisan vendor:publish
```

### Example

```
http://example.com/api/users?q=id:1
```

```
http://example.com/api/users?email~"*@gmail.com"
```

```
http://example.com/api/users?id:<1,2,3>
```

```
http://example.com/api/users?id:NOT <1,2,3>
```

```
http://example.com/api/users?id:[1 TO 100]
```

```
http://example.com/api/users?q=created_at:["-3 months" TO "now"] AND id:NOT [1 TO 100] OR (email:"*@vip.patsnap.com" OR id:888)&sort=id desc&fl=id,email&with=profile
```

### To avoid the cut off of a too long URL by browser or server

Use the optianal header named `search` to transmit the query string.

### Field name pattern

Pattern        |
---------------|
/\^[a-zA-Z_][a-zA-Z0-9_]*$/i |

### Function name pattern

Pattern        |
---------------|
/\^[a-z]\w*$/i |

## Operators

 Operators  | Meaning | Supported Value Types | Example |
:-----------|:--------|:----------------------|:--------|

## Incomplete...
