# InfoFinland Drupal site

Drupal website for the InfoFinland project.

## Environments

Env | Branch | Drush alias | URL                                   | Notes
--- | ------ | ----------- |---------------------------------------| -----
development | * | - | https://drupal-infofinland.docker.so/ | Local development environment
production | main | @main | https://infofinland.fi |  

## Instance specific features

[The Infofinland frontend](https://github.com/City-of-Helsinki/infofinland-ui/) is built using Next.js. Because of this, the `helfi_platform_config` module isn't enabled. The reason is that the Next.js Drupal module has some dependencies that conflict with `helfi_platform_config`. To work around this, a special [`infofinland_dummy` module](./patches/helfi_platform_config/composer.json) is used, which replaces problematic drupal modules. This dummy module allows the compatible parts of `helfi_platform_config` to be used without causing issues.

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

7. After that we need to create and configure the `.env.local` file for the infofinland-ui front.
Copy the example file to .env.local:

``
cp .env.example.local .env.local
``

8. Then go to the infofinland_ui root and run:

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

**Main branch**: `dev`. All feature branches are created from `dev` and merged back with pull requests. All new code must be added with pull requests, not committed directly.

**Production branch:** `main`. Code running in production. Code is merged to `main` with release and hotfix branches.

**Feature branches**: For example, `feature/IFU-000-add-content-type`, Use Jira ticket number in the branch name. Always created from and merged back to `dev` with pull requests after code review and testing.

**Release branches**: Code for future and currently developed releases. Should include the version number, for example: `1.1.0`

**Hotfix branches**: Branches for small fixes to production code. Should include the word hotfix, for example: `IFU-hotfix-drupal-updates`. Remember to also merge these back to `dev`.
