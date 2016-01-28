# We've moved #

We have moved this projec to the [Rediris Forja](https://forja.rediris.es/projects/confia/). Check the wiki page there for more information.

Sorry for any inconvenience.

We've kept this project here because some modules don't make sense in the Forja.

# Overview #

Currently the available modules are:

  * **sqlauth**: Based on the existing sqlauth module.
    * Add an encrypt param to sqlauth:SQL to encrypt the password. Its posible to encrypt the password in the sql-query but sometimes fail (example: pgsql not allow the sha1() function). So now php will make the encryption and not the sql-motor.  Developed by Sixto Martín
    * Add sqlauth:SQLMulti. Allow autheticate the user against a BD included in a set of BD. When login appear the user must choose a source and module will authenticate against it.  Developed by Sixto Martín
    * Add sqlauth:SQLMultiMerge. Authenticate the user against a set of BD, save the data of the different login-sources and later process all data and generate the user attibutes. Developed by Sixto Martín
