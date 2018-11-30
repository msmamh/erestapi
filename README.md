# GitHub Search Code API // By Mohamed Husain


The #idea behind this is to create a generic application to search in different code vendors.



# Frameworks and Libraries  

- CodeIgniter v3.1.9 MVC [ modified and hooked for high speed and optimization high speed ]
https://www.codeigniter.com/ <br>
- codeigniter-restserver => A well setup blank REST API for CodeIgniter<br>
https://github.com/chriskacerguis/codeigniter-restserver/
- Guzzle 6.0 => for promised & optimized http/https requests.<br>
http://docs.guzzlephp.org/en/stable/<br>
- Nahid/Jsonq => for json paths/queries.<br>
https://github.com/nahid/jsonq<br>
# Setup
- Require to get a token from GitHub https://github.com/settings/tokens with full repo & user access. Then update it in config.php
- Its already in production mode. You can place it directly in live server or Dockrize it. Tested on Wamp/Lamp<br>
- You might need to change .htaccess if you want to change the folder /erestapi/ name.<br>
- To change the settings. Please check `application/config/config.php` <br>

# Idea
Define => Fetch => Parse => Transform => Return <br>

- Define Json of your vendor in config. i.e its shipped with GitHub API properties <br>
- The application will fetch from vendor based on settings in config<br>
- The application will transform the JSON automatically<br>
- The application will return the results as JSON, xml, csv and html formats<br>


# Testing
- The application testing units done fully manually. Manual testing links founded on home page<br>
- Placed on live: http://searchcodes.epizy.com/erestapi


# Bugs / Limitation 
- Supports only GET<br>


#The Ideal or other ways.
- To define a union class like this one -> using Laravel: http://tnt.studio/blog/how-to-interact-with-github-api-usgin-laravel <br>
- Using a custom shell command + cURL + jq for parsing/querying jsons path / or php jq wrapper if you want within the code to be called based on config settings<br>
It will produce same out puts
`$ curl -G -H "Authorization: token OAUTH-TOKEN"  https://api.github.com/search/code          \
     --data-urlencode 'q=addClass in:file language:js repo:jquery/jquery' \
     --data-urlencode 'sort=indexed'                   \
     --data-urlencode 'order=asc'                     \
     -H 'Accept: application/vnd.github.json'       \
     | jq '.items[0,1,2] | {owner: (.repository.owner.login), repo: (.repository.full_name), file_name: (.name)}'`<br>

So the generic command will be <br>

`$ curl -G -H "{HEADERS}"  {API}          \
     --data-urlencode '{Query}=value' \
     --data-urlencode '{Sort}=value'                   \
     --data-urlencode '{Order}=value'                     \
     --data-urlencode '{Additional}=value'                     \
     -H '{HEADERS_TWO_IF_REQUIRED}'       \
     | jq '.{items_path}[0,1,2] | {owner: (.{owner_path}), repo: (.{repo_name_path}), file_name: (.{file_name_path})}'` <br>
     
Then just return the output from shell to browser from application level.
But there are a lot of security concerns must be checked if we will use this idea ... although It's one of the best.
