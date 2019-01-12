# Pu-239 Uploader
This can be used to upload a single file or call it from another file and upload multiple torrents.

### If mktorrent is not installed
```
sudo apt-get install mktorrent
```

### Clone the repo
```
git clone https://gitlab.com/darkalchemy/Pu-239-Uploader.git
```

### Install dependancies
```
cd Pu-239-Uploader
composer install
```

### Copy config files and edit
```
cp config.php.example config.php
nano config.php
```

### Running the script
```
php upload.php
```

### To update
```
git pull
composer install
```
