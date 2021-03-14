# ![ApiOpenStudio](public/images/ApiOpenStudio_Logo_Name_Colour.png)

[![License: MPL 2.0](https://img.shields.io/badge/License-MPL%202.0-brightgreen.svg)](https://opensource.org/licenses/MPL-2.0)

Introduction
============

ApiOpenStudio Admin is an open source product, and contributions are welcome.

At heart, ApiOpenStudio is a complete end to end REST API solution.

This codebase is an add-on to [ApiOpenStudio](https://github.com/naala89/api_open_studio)
provides a GUI to completely manage your API, users, roles, resources, etc.

More are planned, to allow more choices of GUI and single-page applications,
and give the community a more choice.

Installation
============

It assumed that you already have ApiOpenStudio up and running.

The quickest way to install ApiOpenStudio is to create a project with composer:

    composer create-project apiopenstudio/apiopenstudio

Or checkout the repository:

    git clone https://github.com/naala89/api_open_studio

Serve ApiOpenStudio and admin through [Docker](https://github.com/naala89/api_open_studio_docker)
or on a server instance.

Setup
=====

If you are serving ApiOpenStudio Admin through docker, you can skip these steps - they are automated by docker.

1. Ensure that you have gulp-cli installed globally:
   
    ```npm install --global gulp-cli```

2. Install from composer:

   ```composer install```

3. Install the npm dependencies:
   
   ```npm install```
   
4. Run gulp:

   ```gulp```

Configuration
=============

   ```cp example.settings.yml settings.yml```

Set the following settings as a minimum:

1. api.url
2. admin.url
3. admin.base_path

Hello world
===========

Requirements
------------

You will need:

* Postman (<a href="https://www.postman.com/downloads/" target="_blank">Download</a>) or similar REST client.

Create a new account
--------------------

Login to [admin.apiopenstudio.local](https://admin.apiopenstudio.local) with your admin account.

Click on "Accounts" in the menu or navigate to [admin.apiopenstudio.local/accounts](https://admin.apiopenstudio.local/accounts).

Click on the Plus icon:

![Add account](includes/images/quick-start/account_add_button.png)

Name new account: "tutorial"

![Create tutorial account](includes/images/quick-start/create_account.png)

You have now created a new top level account:

![Tutorial account created](includes/images/quick-start/new_account.png)

Create a new application
------------------------

Click on "Applications" in the menu or navigate to [admin.apiopenstudio.local/applications](https://admin.apiopenstudio.local/applications).

Click on the Plus icon to create a new application. Assign the application to the "tutorial" account and call it "quick_start".

![Create application](includes/images/quick-start/create_application.png)

You have now created the "quick-start" application that our resource will belong to:

![Application created](includes/images/quick-start/new_application.png)

Configure users and roles
-------------------------

### Create a developer role for the new application

Click on "User Roles" in the menu or navigate to [admin.apiopenstudio.local/user/roles](https://admin.apiopenstudio.local/user/roles).

Click on the plus icon and assign yourself the developer role for Account: tutorial and application: quick_start.

![Create developer role](includes/images/quick-start/create_user_role.png)

You now have permission to create a resource for the newly created quick_start application.

Create a "Hello world!" resource
--------------------------------

This resource will display "Hello world!" in the result in whatever format the client requires,
and will have security that requires an active token from a user with a developer role.
The authentication method will vbe bearer token.

### Define the resource name, description, and URL

Fill out the following fields in the interface:

* Name: ```Hello world```
    * This is the title of the resource that appears in [admin.apiopenstudio.local/resources](https://admin.apiopenstudio.local/resources).
* Description: ```A quick-start hello world resource```
    * This is the description of the resource that appears in [admin.apiopenstudio.local/resources](https://admin.apiopenstudio.local/resources).
* Account: ```tutorial```
    * This assigns the resource to the account tutorial.
* Application: ```quick_start```
    * This assigns the resource to the application quick_start.
* Method: ```GET```
    * This declares the HTTP method.
* URI: ```hello/world```
    * This defines the URI fragment for the request that comes after /<account>/<application>/.
      8 TTL: 30
    * This gives the resource a cache time of 30 seconds.

![Resource definition](includes/images/quick-start/resource_definition_1.png)

So far, we have defined a resource that can be called from (GET) [api.apiopenstudio.local/tutorial/quick_start/hello/world](https://api.apiopenstudio.local/tutorial/quick_start/hello/world).

However, it does nothing and has no security yet.

### Define the security

Add the following snippet to the Security section:

    function: token_role
    id: security
    token:
      function: bearer_token
      id: security_token
    role: Developer

This calls the processor ```token_role```.
We're giving the processor an ID name of "security", so that if there are any bugs we can see where the error is in the result.

The ```token_role``` processor requires 2 inpute:

* token - the requesting user's token.
* role - the role to validate the requesting user against.

```token``` will use another processor to pass its result into token_role.
This is ```bearer_token```. This will return the bearer token value from the request header.
We will assign this an ID name of "security_token".

```role``` will not require processing from another processor, because this does not need to be dynamic.
So we're using a static string: "Developer".

![Resource security](includes/images/quick-start/resource_definition_2.png)

### Define the process

Add the following snippet to the Process section:

    function: var_str
    id: process
    value: 'Hello world!'

This will use a single processor: ```var_str```.
This processor returns the value of a strictly typed string.

It's input value does not need to be dynamic here, so we're giving it a static string value.

![Resource process](includes/images/quick-start/resource_definition_3.png)

### Save

Click on the ```Upload``` button.

The resource will be parsed and checked for any error, and saved to the database.

If you navigate back to [admin.apiopenstudio.local/resources](https://admin.apiopenstudio.local/resources),
you should see your new resource.

![Resource created](includes/images/quick-start/resource_created.png)

If you click on the download button in the listing for ```Hello world``` and select YAML format,
it should look like this:

    name: 'Hello world'
    description: 'A quick-start hello world resource'
    uri: hello/world
    method: get
    appid: 2
    ttl: ''
    security:
        function: token_role
        id: security
        token:
            function: bearer_token
            id: security_token
        role: Developer
    process:
        function: var_str
        id: process
        value: 'Hello world'

You can edit and upload this yaml file as you wish.

Run the new resource
--------------------

Open up your REST client

### Get a new token for your user

* Method: POST
* URL: https://api.apiopenstudio.local/apiopenstudio/core/login
* Header:
    * Accept: application/json
* Body:
    * x-www-form-urlencoded
    * fields:
        * username: <username>
        * password: <password>

![User login request header](includes/images/quick-start/user_login_header.png)

![User login request body](includes/images/quick-start/user_login_body.png)

The result should be something similar to:

    {
        "token": "13ae430eb19a6651378e22e3a37de8cf",
        "uid": 2
    }

Copy the value for the token.

### Run Hello world!

* Method: GET
* URL: https://api.apiopenstudio.local/tutorial/quick_start/hello/world
* Header:
    * Accept: application/json
    * Authorization: Bearer <token>

The result should be something similar to:

    "Hello world!"

![Hello world result](includes/images/quick-start/hello_world_result.png)

If we change the Accept value in the header to ```application/xml```,
we will get something similar to:

    <?xml version="1.0"?>
    <apiopenstudioWrapper>Hello world!</apiopenstudioWrapper>
