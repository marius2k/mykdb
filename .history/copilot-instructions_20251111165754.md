# General Information about mykdb project

 -> mykdb is running in a docker container, check Dockerfile and docker-compose.yml for setup details; 
 -> check the tables structure directly in database "knowledge_db" (root/no pass), not in .sql files. the files might not be up to date;
 -> header and footer of each page is defined in includes/ (header.php and footer.php);
 -> database operations are managed with Database class, defined in assets/classes/Database.php;
 -> general configuration file for the app is assets/config/config.php;
 -> javascript files are in assets/js/;
 -> search page is public/index.php;
 -> admin panel is in public/admin/;
 -> analytics related code is in assets/js/analytics.js and assets/js/search-analytics.js;
 -> search analytics backend API is in public/api/bkd_search_analytics.php;
 -> navigation menu is implemented in function generateNavBar2() in defined in includes/functions.php;
 -> themes styles are in assets/css/;

# Article Management System

-> articles are stored in database table 'articles' and article_versions;
-> articles table contains all versions of articles which are online, visible by visitors on main page;
-> article_versions table contains all historical versions of articles, including drafts and unpublished changes;
-> online version of an article has status = published or disabled in articles table. if status = disabled, article is not visible to visitors;
-> article_versions contains all versions of articles and have possible statuses: draft, pending, approved and disabled;
-> article_versions contains also the copy of the online version of the article, with is_online=1; the other versions of an article have is_online=0;
-> article_versions.article_id is foreign key to articles.id;
-> the flow of an article is: draft -> pending -> approved -> published (in articles table). an article can be disabled only if it is online (is_online=1); by disableing an article, it is removed from public view but its data is kept in the database: set articles.status = disabled and article_versions.status = disabled for the online version (is_online=1);
-> creation of a new article is done by inserting a new record in articles table with status = disabled and a new record in article_versions with is_online=0 and status = draft or pending;
-> publishing an article is done by updating fields in articles table and setting articles.status = published and article_versions.is_online=1 and article_versions.status = approved for the online version; previous online version is kept in article_versions with is_online=0;
-> in Admin->Articles->View Articles page are listed by default (at first load) the online versions of articles, the versions from article_versions where is_online=1; there is a column called "Version" which shows the version number from article_versions.version; When change the Version, the table row should be updated to show the selected version of the article from article_versions table;


