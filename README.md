# MV framework core
This repo contains the core classes and admin panel of [MV framework](https://github.com/makscraft/mv-framework). You can update your MV project by syncing it with the latest version of the core and admin panel.

Updating via composer
---
```
cd your_project
composer update
```
- After the update completes, the adminpanel directory will be automatically updated with the latest files.


Manual update
---
- Make sure your project was installed manually and does not have the **vendor** directory.
- Create a backup of your project.
- Download archive from the [last release of this repo](https://github.com/makscraft/mv-core/releases).
- Copy the following folders over your project: **adminpanel**, **config**, **core**.
- Empty the **/userfiles/cache** directory.
