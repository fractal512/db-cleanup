## DB-Cleanup (Wordpress plugin)

Wordpress database cleaning up tool. Initial this tool was made specially for specific sites running on Wordpress CMS but it can work with any Wordpress site.

### Attention!!!

Don't forget to make the database backup before using this plugin!

## Preview

![DB-Cleanup plugin interface](https://github.com/fractal512/db-cleanup/blob/master/blob/assets/db-cleanup.png?raw=true)

### Features:

- Removes post revisions with related meta data (cleans tables: "{prefix}_posts", "{prefix}_term_relationships", "{prefix}_postmeta");
- Searches and removes specific duplicates with meta keys '_pagemeta_title', '_pagemeta_description', '_pagemeta_keywords' from "{prefix}_postmeta" table (some SEO plugins leave);
- Cleans "{prefix}_options" table of transients entries;
- Optimizes tables (tables files defragmentation).
