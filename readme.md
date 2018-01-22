### 使用示例

<pre>
<code>
use SearchQuery\SearchQueryTrait;
 
class SdkClient {
    
    use SearchQueryTrait;
     
    public function get() {
        $exp = $this->where('id', 1)->orWhere(function ($query) {
            $query->where('name', 'zhangsan');
            $query->orWhereNotIn('name', 1, 10);
        })->orWhere(function($query) {
            $query->whereNotInOpenInterval('age', 20,50);
            $query->orWhere('age', 100);
        })->buildQuery();
        
        echo $exp;
    }
}
</code>
</pre>

### 支持的方法

1. where
2. whereNot
3. orWhere
4. orWhereNot
5. whereInOpenInterval
6. whereNotInOpenInterval
7. orWhereInOpenInterval
8. orWhereNotInOpenInterval
9. whereInClosedInterval
10. whereNotInClosedInterval
11. orWhereInClosedInterval
12. orWhereNotInClosedInterval
13. whereIn
14. whereNotIn
15. orWhereIn
16. orWhereNotIn
