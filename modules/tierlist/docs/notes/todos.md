# todo:
- switch /public/users to folder outside repo

- before on form submit for addFilesToList.php, add check to see if number of files and total size are within limits.
This is managed by php.ini. I updated these properties values:
```ini
upload_max_filesize = 300M
max_file_uploads=200
post_max_size = 0 # this removes the size limit
memory_limit = 1024M
```

- get rid of exit() in listEditor.php and replace with error message displayed on the user interface --done


# completed

### steps for create user page
- create front end page --done
- form to enter username and submit button --done
- on submit, php validates the input --done
- create a user in the database --done
- create the user's home directory --done
- add the username and home folder to the session data --done

### backend
- handle file uploads --done
- separate by username --done
- Add create user page which will take just a username. --done

### frontend
- clean up and refactor code to make more modular and remove redundancy -done
- import existing tier list from json -done
- delete tier list data -done
- make "move to no tier" button -- done