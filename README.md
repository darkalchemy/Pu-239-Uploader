# Pu-239 Uploader
This can be used to upload a single file or call it from another file and upload multiple torrents.  
Point the upload.php script to a single folder and upload 1 torrent or point the loop_example.php script a folder of folders and upload many torrents with the same category.  
This script gets the torrent name from the parent folder, so name it appropriately.  

### If mktorrent is not installed
```
sudo apt-get install mktorrent
```

### Clone the repo
```
git clone https://github.com/darkalchemy/Pu-239-Uploader.git
```

### Install dependencies
```
cd Pu-239-Uploader
composer install
```

### Edit config.php
```
cp config.php.example config.php
nano config.php
```

### To run for single upload
```
php upload.php
php upload.php category /path/to/parent_folder /path/to/descr[optional]
```

### To run for multiple uploads
```
php upload_loop.php category category /path/to/parent/folder/
```

### To update
```
git pull
composer install
```
