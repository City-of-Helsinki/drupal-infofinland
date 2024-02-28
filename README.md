# InfoFinland Drupal 9 site

Drupal 9 website for the InfoFinland project.

## Environments

Env | Branch | Drush alias | URL | Notes
--- | ------ | ----------- | --- | -----
development | * | - | https://drupal-infofinland.docker.so/ | Local development environment
production | main | @main | TBD | Not implemented yet

## Requirements

You need to have these applications installed to operate on all environments:

- [Docker](https://github.com/druidfi/guidelines/blob/master/docs/docker.md)
- [Stonehenge](https://github.com/druidfi/stonehenge)
- For theme development: [node >=16.6](https://nodejs.org/en/) is required. [NVM](https://nodejs.org/en/) is recomennended for node version management. .nvmrc is included in the theme
- For the new person: Your SSH public key needs to be added to servers

## Create and start the environment

For the first time (new project):

``
$ make new
``

Stop project:

``
$ make stop
``

Start project after stopping it:

``
$ make up
``

Stop project and remove containers:

``
$ make down
``

Start project, rebuild and update configuration:

``
$ make up; make build; make post-install
``

Install fresh Drupal site from existing configuration:

``
$ make build; make drush-si; make post-install
``

Start project, update all packages and sync db from local sql dump:

``
$ make fresh
``

## Update Drupal and composer modules

Install all modules and composer packages:

``
$ make build
``

Update all modules and composer packages:

``
$ make composer-update
``

Update only Drupal core:

``
$ make drupal-update
``

**Note:** After updates, clear caches, run database updates and export possibly changed configuration:

``
$ make drush-cr; make drush-updb; make drush-cex
``

Update Composer.lock if outdated (after merges, etc):

```
# Login into app container first:
$ make shell

# Update lock file:
$ composer update --lock
```

## Configuration management and workflow

When checking out new branch run `make drush-cim` to import new config changes to the database.

Remember always run `make drush-cex` after you have made configuration changes and before you pull or checkout new code. Remember also to commit your config changes.

Export settings:

``
$ make drush-cex
``

Import settings:

``
$ make drush-cim
``

When checking out new branch run `make drush-cim` to import new config changes to the database.

Remember always run `make drush-cex` after you have made configuration changes and before you pull or checkout new code. Remember also to commit your config changes.

## Setting up the site locally with frontend

### Setting up the Drupal site.

1. Clone the site code:

``
git clone git@github.com:City-of-Helsinki/drupal-infofinland.git
``

2. Go to the folder and change to dev-branch:

``
git fetch && git checkout dev
``

3. Then you need database. We need to have the database on the root of the project with a name dump.sql.
You can get it from production like this:

* Log into VPN.
* Go to the address https://oauth-openshift.apps.platta.hel.fi/oauth/token/request, press _Display Token_ and copy the
command that starts `oc login --token=...` from the page. Paste that to your terminal screen.
* Select which project you want to get the database for with `oc project [project_name]` for example
`oc project hki-kanslia-infofinland-prod`.
* Then get the pods of that project with oc get pods.
* Now select a pod and save the pod to variable `pod=[pod_name]` for example `pod=infofinland-drupal-75-8n6qn`.
* Then use this command to dump the database to your local:
`oc rsh $pod drush sql:dump --structure-tables-key=common --result-file=/tmp/dump.sql && oc rsync $pod:/tmp/dump.sql . && oc rsh $pod rm /tmp/dump.sql`
* Now you have a database in a file called dump.sql on the root of the project.
* There might be issues with the database collation so run `sed -i '' 's/utf8mb4_0900_ai_ci/utf8mb4_swedish_ci/g' dump.sql`

4. Now that we have the database we need to get the drupal instance up and running.

``
make fresh
``

**After a while you should have your local Infofinland Drupal running.**

### Setting up the NextJS front.
1. Install Yarn if not already installed:

``
npm install -g yarn
``
2. Now you need to close your VPN so that you can use git.
3. Next clone the UI to your local (not inside the drupal-infofinland folder but to the same level):

``
git clone git@github.com:City-of-Helsinki/infofinland-ui.git
``
4. Go to the infofinland-ui folder and change the branch to develop:

``
git fetch && git checkout develop
``

6. Install dependencies:

``
yarn
``

6. Now go to the root of your local drupal infofinland instance and run:

``
make shell
``

7. Inside the shell create a folder called keys. This folder will be used later.

``
mkdir keys
``

8. Exit the shell and log into your drupal.

``
make drush-uli
``

9. Next go to Simple Oauth settings here:

https://drupal-infofinland.docker.so/en/admin/config/people/simple_oauth

10. Here press the _Generate keys_ button and when it asks for a directory for the keys give it the `/app/keys` folder.
This generates now public and private keys for the keys folder.

11. Next **remember** to click on the _Save configuration_ button.

12. After that we need to create and configure the `.env.local` file for the infofinland-ui front.
Copy the example file to .env.local:

``
cp .env.example.local .env.local
``

13. Now that you have the file you can open it in an editor and same time go to your drupal-infofinland instance
and its next module configurations:

https://drupal-infofinland.docker.so/en/admin/config/services/next

14. From this page you can copy the _Preview secret_ hash to the env file:

``
DRUPAL_PREVIEW_SECRET=[hash]
``

15. Then click on the item that is listed there and select _Edit_.

16. For the _Base URL_ you need to change the value to http://localhost:3000 and for the _Preview URL_ you need to change
the value to http://localhost:3000/api/preview. The preview secret should be the same that you copied to your
`.env.local` file. Press _Save_.

17. Now we need a consumer. This will be done by going to this url:

https://drupal-infofinland.docker.so/en/admin/config/services/consumer

18. Here delete the existing consumer and press _Add consumer_ after that. The label should be `NextJS` and for the user
select a user called `nextjs`. It should have all the required roles and permissions. For the new secret add a secret
of you choise but remember it. Make sure to select `Nextjs` for the _Scopes_. Press _Save_.

19. Now for the .env.local file you need to add the _Uuid_ of the consumer as the Drupal client ID:

``
DRUPAL_CLIENT_ID=[uuid]
``

20. On the same file you need the secret that you just made up for the consumer as the Drupal client secret:

``
DRUPAL_CLIENT_SECRET=[your_secret]
``

21. Now for the last step enable the Next Telemetry. This can be done by setting NEXT_TELEMETRY_DISABLED value to 0 on the
.env.local file:

``
NEXT_TELEMETRY_DISABLED=0
``

22. Now we are done with the .env.local file, and we can save it.

23. Then go to the infofinland_ui root and run:

``
yarn dev
``

**This should start the frontend and the frontend should be able to talk with the backend now and you should be able to
see the working frontend on http://localhost:3000/ address.**

Useful documentation if you run into problems:

https://helsinkisolutionoffice.atlassian.net/wiki/spaces/IFU/pages/7735148873/Drupal+Next.js+configurations

## Other useful commands

### After pulling latest changes, run all the updates:
$ make drush-deploy

```
# Login to app container
$ make shell

# Login to Drupal with Drush:
$ make drush-uli

# Create sql dump from local site
$ make drush-create-dump

# Check Drupal coding style
$ make lint-drupal

# Automatically fix Drupal coding style errors:
$ make fix-drupal
```

### Coding standards
Follow Drupal's coding standards: https://www.drupal.org/docs/develop/standards

City of Helsinki's coding standars and best practices: https://dev.hel.fi/

Check for coding style violantions by running `$ make lint-drupal`

### Gitflow workflow
The Gitflow workflow is followed, with the following conventions:

**Main branch**: `develop`. All feature branches are created from `develop` and merged back with pull requests. All new code must be added with pull requests, not committed directly.

**Production branch:** `main`. Code running in production. Code is merged to `main` with release and hotfix branches.

**Feature branches**: For example, `feature/IFU-000-add-content-type`, Use Jira ticket number in the branch name. Always created from and merged back to `develop` with pull requests after code review and testing.

**Release branches**: Code for future and currently developed releases. Should include the version number, for example: `1.1.0`

**Hotfix branches**: Branches for small fixes to production code. Should include the word hotfix, for example: `IFU-hotfix-drupal-updates`. Remember to also merge these back to `develop`.
