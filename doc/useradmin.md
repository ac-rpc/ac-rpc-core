##Research Project Calculator User Types

### Administrative
####Super User ####
The super user is defined in the application's configuration file (`AUTH_SUPERUSER`), so that no matter what happens to the account, it can always be recovered. Once the superuser is named inside the config file, you need to create an account with that email address and you'll automatically become the superuser. Even if the account is deleted, it can always just be recreated with the same email address.  (Incidentally, the application won't allow the superuser to delete his or her own account).

#### Administrator
Administrators have the ability to name other users as administrators or publishers, plus the ability to create and publish assignment templates AND edit or delete templates created by any other admins or publishers.

#### Publisher
Publishers have the ability to create new assignment templates, and can edit or delete their own templates, but cannot modify of delete templates authored by anyone else.

### Managing Administrative Roles
_To assign privileges to other users, you must be logged in as an Administrator or as the Super User._

 1. The user you are assigning privileges for must already exist in the system.
 2. On the **Administration** page, search for the user's email address. If found, the user will be added to the Current Privileged Users table above.
 3. Select either **Administrator** or **Publisher** as the user's new role.
 4. To remove all privileges from the user, choose **Revoke All**.

### User Roles
_Note: There's nothing to prevent a student from registering as a teacher/instructor, but the two types get slightly different views of assignments & templates._

#### Student
Students can see the assignment steps, and are provided space beside each step to add their own notes and track progress.  The notes are not visible to others if an assignment is shared out.

#### Teacher
Teacher accounts see the same steps that students see, plus additional information for instructors (lesson planning, links to additional resource documents). There isn't a separate Notes box, but the teacher info can be edited in each step.  
