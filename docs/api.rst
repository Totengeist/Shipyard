======================
API Reference
======================

Reference information to help you get started integrating Shipyard
functionality into your application via our REST API.

.. note::

   - This documentation is under construction and incomplete.
   - Bearer tokens listed as "optional" are only optional for clients that support cookies.

.. contents:: Table of contents
    :local:
    :depth: 3

General
-------

.. http:get:: /api/v1/version

   Get version information for the current Shipyard instance.

   This query can be used as a sanity check for new clients.

   **Example request**:

   .. sourcecode:: http

      POST /api/v1/version HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: text/javascript

      {
        "app": "Shipyard",
        "version": "0.1.1"
      }

   :statuscode 200: no error

Account Management
------------------

.. http:post:: /api/v1/register

   Create a new user account.

   **Example request**:

   .. sourcecode:: http

      POST /api/v1/register HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: text/javascript

      {
        "user": {
          "name": "A Standard User",
          "email": "user@example.com",
          "ref": "d5052196e",
          "updated_at": "2024-02-15 19:42:06",
          "created_at": "2024-02-15 19:42:06"
        }
      }

   :query name: the username, which can contain spaces and special characters.
   :query email: a valid email address to send the activation link to.
   :query password: a strong password
   :query password_confirmation: the same strong password, to verify it wasn't misspelled
   :reqheader Authorization: optional Bearer token to authenticate
   :statuscode 200: no error
   :statuscode 422: the input was invalid or the email was already used

.. http:post:: /api/v1/activate/(token)

   Activate a new user account. Unactivated accounts will be unable to :http:post:`/api/v1/login`.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/activate/f49c499f7d7b006d66bcc9a5ad11ee5491ecfe95 HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "name": "A Standard User",
        "ref": "d5052196e",
        "email": "user@example.com",
        "created_at": "2024-02-15 19:42:06",
        "updated_at": "2024-02-15 20:03:17"
      }

   :param token: the activation token sent to the user's email
   :statuscode 200: no error
   :statuscode 404: the token was not found

.. http:post:: /api/v1/login

   Log in a user.
   
   The ``session_id`` token should be stored by the client and used as a
   Bearer token on future requests. Logging the user out should be as simple
   as deleting the token in the client's storage, but you can also :http:post:`/api/v1/logout`
   to invalidate the session on the server.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/login HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: text/javascript

      {
          "name": "A Standard User",
          "ref": "d5052196e",
          "email": "user@example.com",
          "created_at": "2024-02-15 19:42:06",
          "updated_at": "2024-02-15 20:03:17",
          "session_id": "1vcf2evvf51t0o9l7n0f38gr24",
          "roles": []
      }

   :query email: the user's email address
   :query password: the user's password
   :statuscode 200: no error
   :statuscode 401: the account doesn't exist, the password is incorrect,
     or the account has not been activated

.. http:post:: /api/v1/logout

   Log out the current session.

   **Example request**:

   .. sourcecode:: http

      POST /api/v1/logout HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: text/javascript

      {
        "message":"You have been logged out."
      }

   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error

.. http:get:: /api/v1/me

   Information about the currently logged in user. Use this to check if your session is logged in.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/me HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "name": "A Standard User",
        "ref": "d5052196e",
        "email": "user@example.com",
        "created_at": "2024-02-15 19:42:06",
        "updated_at": "2024-02-15 20:03:17",
        "session_id": "1vcf2evvf51t0o9l7n0f38gr24",
        "roles": []
      }

   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: no user is logged in

Ship Management
---------------

.. http:get:: /api/v1/ship

   A paginated list of ships.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/ship HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "current_page": 1,
        "data": [
          {
            "ref": "5abe24b6a",
            "title": "Ship 1",
            "description": "An example ship.",
            "downloads": 0,
            "created_at": "2024-02-15 20:56:49",
            "updated_at": "2024-02-15 20:56:49",
            "user": {
              "name": "Test User 1",
              "ref": "a4c836a85"
            },
            "primary_screenshot": []
          },
          {
            "ref": "96a9d0a9e",
            "title": "blanditiis rem nemo",
            "description": "Aut debitis ipsam saepe sed iusto sint. Laboriosam odio eveniet expedita dolorem et ut. Esse eum molestiae et veritatis ut velit dicta laudantium.",
            "downloads": 97320,
            "created_at": "2024-02-15 20:58:01",
            "updated_at": "2024-02-15 20:58:01",
            "user": {
              "name": "Test User 1",
              "ref": "a4c836a85"
            },
            "primary_screenshot": [
              {
                "ref": "36a269e9e",
                "description": "Et explicabo perspiciatis libero. Rem illo ea voluptatem. Et vitae aut sapiente perferendis officia repudiandae hic quaerat. Eligendi consequuntur ut explicabo eveniet aut quo.",
                "created_at": "2024-02-15 20:58:01",
                "updated_at": "2024-02-15 20:58:01"
              }
            ]
          },
          {
            "ref": "713d63cc2",
            "title": "et voluptatem enim",
            "description": "Quo fugit voluptatem soluta voluptate ullam possimus inventore et. Et voluptates nesciunt vero dolor expedita et excepturi. Odio aut amet omnis repudiandae.",
            "downloads": 90023,
            "created_at": "2024-02-15 20:58:01",
            "updated_at": "2024-02-15 20:58:01",
            "user": {
              "name": "Alvah Mraz",
              "ref": "362702654"
            },
            "primary_screenshot": [
              {
                "ref": "c9f5d4d03",
                "description": "Vitae nobis aut velit omnis. Dolores similique corporis quo sunt ut. Nihil ipsum nostrum quo ipsam qui beatae ex. Saepe enim adipisci dolore eum labore.",
                "created_at": "2024-02-15 20:58:01",
                "updated_at": "2024-02-15 20:58:01"
              }
            ]
          },
          {
            "ref": "9062614bc",
            "title": "at asperiores labore",
            "description": "Deserunt illo qui sunt qui. Fuga fugiat aut rerum alias. Ad enim suscipit ratione et ea.",
            "downloads": 30458,
            "created_at": "2024-02-15 20:58:02",
            "updated_at": "2024-02-15 20:58:02",
            "user": {
              "name": "Palma Jaskolski",
              "ref": "f64a4aeef"
            },
            "primary_screenshot": [
              {
                "ref": "a049c977d",
                "description": "Omnis amet unde quis dolore inventore. Rerum sint veniam molestias nihil id asperiores. Sequi dolor libero autem sint corporis similique provident.",
                "created_at": "2024-02-15 20:58:02",
                "updated_at": "2024-02-15 20:58:02"
              }
            ]
          },
          {
            "ref": "91a537b51",
            "title": "facilis dolore atque",
            "description": "Est qui aut est sunt explicabo quisquam perspiciatis eum. Quod fugit officiis aliquam dolores distinctio maxime ut. Dolores in vitae ut. Neque adipisci qui molestias et quo qui consequatur nihil.",
            "downloads": 55341,
            "created_at": "2024-02-15 20:58:02",
            "updated_at": "2024-02-15 20:58:02",
            "user": {
              "name": "administrator",
              "ref": "c72d235d6"
            },
            "primary_screenshot": [
              {
                "ref": "a2209078f",
                "description": "Nemo iusto aliquid adipisci explicabo explicabo quia non. Nemo sed adipisci non voluptas ab omnis dignissimos. Dolor et qui earum.",
                "created_at": "2024-02-15 20:58:02",
                "updated_at": "2024-02-15 20:58:02"
              }
            ]
          }
        ],
        "first_page_url": "/?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "/?page=3",
        "next_page_url": "/?page=2",
        "path": "/",
        "per_page": 5,
        "prev_page_url": null,
        "to": 15,
        "total": 45
      }

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :statuscode 200: no error

.. http:get:: /api/v1/ship/(ref)

   Information about a specific ship, identified by ``ref``.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/ship/5abe24b6a HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "ref": "06f5f4644",
        "title": "odit mollitia enim",
        "description": "Minima perferendis nam ipsum eveniet alias odio. Blanditiis quos eius voluptatibus quia non. Qui dignissimos tempore sit at voluptatem debitis officia. Dolor nulla non blanditiis cumque.",
        "downloads": 89439,
        "created_at": "2024-02-15 20:58:05",
        "updated_at": "2024-02-15 20:58:05",
        "user": {
          "name": "Arnulfo Hand",
          "ref": "4cebc8209"
        }
      }

   :param ref: the unique ID of the ship to query
   :statuscode 200: no error
   :statuscode 404: the ship does not exist

.. http:post:: /api/v1/ship

   Upload a new ship file.

   **Example request**:

   .. sourcecode:: http

      POST /api/v1/ship HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "ref": "19fb7fa39",
        "title": "et ut voluptatem",
        "description": "Ipsa eligendi quia dolorem sit amet illo. Magnam quae voluptas mollitia. Nemo et asperiores adipisci dolor cumque.",
        "downloads": 19519,
        "created_at": "2024-02-15 20:58:03",
        "updated_at": "2024-02-15 20:58:03",
        "user": {
          "name": "Maxie Rice",
          "ref": "01a8a22ec"
        }
      }

   :param file: the ship file being uploaded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or banned from uploading

.. http:get:: /api/v1/ship/(ref)/download

   Download a ship file.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/ship/5abe24b6a/download HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   :param ref: the unique ID of the ship to query
   :statuscode 200: no error
   :statuscode 404: the ship does not exist

.. http:get:: /api/v1/ship/(ref)/screenshots

   A list of screenshots for a ship.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/ship/8ef20cff9/screenshots HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      [
        {
          "ref": "6ddc196f9",
          "description": "Similique amet nulla sed rem. Sunt quia voluptatem ut consequuntur commodi. Cupiditate ipsum dicta magni est labore recusandae.",
          "created_at": "2024-02-17 00:22:52",
          "updated_at": "2024-02-17 00:22:52",
          "primary": 0
        },
        {
          "ref": "8b198db69",
          "description": "Tempore maiores ut repellendus iusto modi omnis non. Sapiente maxime assumenda dignissimos enim perferendis earum dolore. Deserunt beatae ducimus praesentium ipsum ut placeat error.",
          "created_at": "2024-02-17 00:22:52",
          "updated_at": "2024-02-17 00:22:52",
          "primary": 1
        }
      ]

   :param ref: the unique ID of the ship to get screenshots for
   :statuscode 200: no error
   :statuscode 404: the ship does not exist

.. http:post:: /api/v1/ship/(ref)

   Edit an existing ship.

   :param ref: the unique ID of the ship to edit
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 404: the ship does not exist

.. http:post:: /api/v1/ship/(ref)/upgrade

   Replace an existing ship with a new version.

   Older versions will still be accessible. This allows users to upgrade ships to support new
   features added to The Last Starship. The ``ref`` will continue to point to the older version,
   but it's page will display a notice that a newer version is available.

   **Example request**:

   .. sourcecode:: http

      POST /api/v1/ship/19fb7fa39/upgrade HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   .. sourcecode:: http

      HTTP/1.1 200 OK
      Content-Type: application/json

      {
        "ref": "8ef20cff9",
        "title": "et ut voluptatem",
        "description": "Ipsa eligendi quia dolorem sit amet illo. Magnam quae voluptas mollitia. Nemo et asperiores adipisci dolor cumque.",
        "downloads": 19519,
        "created_at": "2024-02-15 20:58:03",
        "updated_at": "2024-02-15 20:58:03",
        "user": {
          "name": "Maxie Rice",
          "ref": "01a8a22ec"
        }
      }

   :param file: the ship file being uploaded
   :param ref: the unique ID of the ship to be upgraded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the ship
   :statuscode 404: the ship does not exist

.. http:post:: /api/v1/ship/(ref)/screenshots

   Adds one or more screenshots to an existing ship.

   :param ref: the unique ID of the ship to add screenshots to
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the ship
   :statuscode 404: the ship does not exist

.. http:delete:: /api/v1/ship/(ref)

   Delete an existing ship.

   :param ref: the unique ID of the ship to delete
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the ship
   :statuscode 404: the ship does not exist

Save Management
---------------

.. http:get:: /api/v1/save

   A paginated list of saves.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/save HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   **Example response**:

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of saves per page to return, defaulting to 15 and limited to 100 or less
   :statuscode 200: no error

.. http:get:: /api/v1/save/(ref)

   Information about a specific save, identified by ``ref``.

   **Example request**:

   **Example response**:

   :param ref: the unique ID of the save to query
   :statuscode 200: no error
   :statuscode 404: the save does not exist

.. http:post:: /api/v1/save

   Upload a new save file.

   **Example request**:

   **Example response**:

   :param file: the save file being uploaded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or banned from uploading

.. http:get:: /api/v1/save/(ref)/download

   Download a save file.

   **Example request**:

   .. sourcecode:: http

      GET /api/v1/ship/5abe24b6a/download HTTP/1.1
      Host: example.com
      Accept: application/json, text/javascript

   :param ref: the unique ID of the ship to query
   :statuscode 200: no error
   :statuscode 404: the save does not exist

.. http:get:: /api/v1/save/(ref)/screenshots

   A list of screenshots for a save.

   **Example request**:

   **Example response**:

   :param ref: the unique ID of the save to get screenshots for
   :statuscode 200: no error
   :statuscode 404: the save does not exist

.. http:post:: /api/v1/save/(ref)

   Edit an existing save.

   :param ref: the unique ID of the save to edit
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 404: the save does not exist

.. http:post:: /api/v1/save/(ref)/upgrade

   Replace an existing save with a new version.

   Older versions will still be accessible. This allows users to upgrade saves to support new
   features added to The Last Starship. The ``ref`` will continue to point to the older version,
   but it's page will display a notice that a newer version is available.

   **Example request**:

   **Example response**:

   :param file: the save file being uploaded
   :param ref: the unique ID of the save to be upgraded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the save
   :statuscode 404: the save does not exist

.. http:post:: /api/v1/save/(ref)/screenshots

   Adds one or more screenshots to an existing save.

   :param ref: the unique ID of the save to add screenshots to
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the save
   :statuscode 404: the save does not exist

.. http:delete:: /api/v1/save/(ref)

   Delete an existing save.

   :param ref: the unique ID of the save to delete
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the save
   :statuscode 404: the save does not exist

Mod Management
--------------

.. http:get:: /api/v1/modification

   A paginated list of mods.

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :statuscode 200: no error

.. http:get:: /api/v1/modification/(ref)

   Information about a specific mod, identified by ``ref``.

   :param ref: the unique ID of the mod to query
   :statuscode 200: no error
   :statuscode 404: the mod does not exist

.. http:post:: /api/v1/modification

   Upload a new mod file.

   :param file: the mod file being uploaded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or banned from uploading

.. http:get:: /api/v1/modification/(ref)/download

   Download a mod file.

   :param ref: the unique ID of the mod to query
   :statuscode 200: no error
   :statuscode 404: the mod does not exist

.. http:get:: /api/v1/modification/(ref)/screenshots

   A list of screenshots for a mod.

   :param ref: the unique ID of the mod to get screenshots for
   :statuscode 200: no error
   :statuscode 404: the mod does not exist

.. http:post:: /api/v1/modification/(ref)

   Edit an existing mod.

   :param ref: the unique ID of the mod to edit
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 404: the mod does not exist

.. http:post:: /api/v1/modification/(ref)/upgrade

   Replace an existing mod with a new version.

   Older versions will still be accessible. This allows users to upgrade mods to support new
   features added to The Last Starship. The ``ref`` will continue to point to the older version,
   but it's page will display a notice that a newer version is available.

   :param file: the mod file being uploaded
   :param ref: the unique ID of the mod to be upgraded
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the mod
   :statuscode 404: the mod does not exist

.. http:post:: /api/v1/modification/(ref)/screenshots

   Adds one or more screenshots to an existing modification.

   :param ref: the unique ID of the ship to add screenshots to
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the mod
   :statuscode 404: the mod does not exist

.. http:delete:: /api/v1/modification/(ref)

   Delete an existing mod.

   :param ref: the unique ID of the mod to delete
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the mod
   :statuscode 404: the mod does not exist

Screenshot Management
---------------------

.. http:get:: /api/v1/screenshot/(ref)

   Information about a specific screenshot

   :param ref: the unique ID of the screenshot to edit
   :statuscode 200: no error
   :statuscode 404: the screenshot (or the item it belongs to) does not exist

.. http:post:: /api/v1/screenshot/(ref)

   Edit an existing screenshot.

   :param ref: the unique ID of the screenshot to edit
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 404: the screenshot does not exist

.. http:delete:: /api/v1/screenshot/(ref)

   Deletes an existing screenshot.

   :param ref: the unique ID of the screenshot to delete
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the screenshot
   :statuscode 404: the screenshot does not exist

Administrative
--------------

User Management
***************

.. http:post:: /api/v1/user/(user_id)

   Edit an existing user.

   :param ref: the unique ID of the user to edit
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 404: the user does not exist

.. http:delete:: /api/v1/user/(user_id)

   Delete an existing user.

   :param ref: the unique ID of the user to delete
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error
   :statuscode 401: not logged in or not the owner of the user
   :statuscode 404: the user does not exist

Tag & Release Management
************************

.. http:get:: /api/v1/tag

   A paginated list of tags.

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error

.. http:get:: /api/v1/tag/(slug)

   Information about a specific tag.

   :statuscode 200: no error
   :statuscode 404: the tag does not exist
   :reqheader Authorization: optional bearer token to authenticate

.. http:post:: /api/v1/tag
.. http:post:: /api/v1/tag/(slug)
.. http:delete:: /api/v1/tag/(slug)

   Delete an existing tag.

   :reqheader Authorization: optional bearer token to authenticate
.. http:get:: /api/v1/release

   A paginated list of releases.

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error

.. http:get:: /api/v1/release/(slug)

   Information about a specific release.

   :statuscode 200: no error
   :statuscode 404: the release does not exist
   :reqheader Authorization: optional bearer token to authenticate

.. http:post:: /api/v1/release
.. http:post:: /api/v1/release/(slug)
.. http:delete:: /api/v1/release/(slug)

   Delete an existing release.

   :reqheader Authorization: optional bearer token to authenticate

Permission & Role Management
****************************

.. http:get:: /api/v1/permission

   A paginated list of permissions.

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error

.. http:get:: /api/v1/permission/(slug)

   Information about a specific permission

   :statuscode 200: no error
   :statuscode 404: the permission does not exist
   :reqheader Authorization: optional bearer token to authenticate

.. http:post:: /api/v1/permission
.. http:post:: /api/v1/permission/(slug)
.. http:delete:: /api/v1/permission/(slug)

   Delete an existing permission.

   :reqheader Authorization: optional bearer token to authenticate

.. http:get:: /api/v1/role

   A paginated list of roles.

   :query page: an optional page number, defaulting to 1
   :query per_page: an optional number of ships per page to return, defaulting to 15 and limited to 100 or less
   :reqheader Authorization: optional bearer token to authenticate
   :statuscode 200: no error

.. http:get:: /api/v1/role/(slug)

   Information about a specific role

   :statuscode 200: no error
   :statuscode 404: the role does not exist
   :reqheader Authorization: optional bearer token to authenticate

.. http:post:: /api/v1/role
.. http:post:: /api/v1/role/(slug)
.. http:delete:: /api/v1/role/(slug)

   Delete an existing role.

   :reqheader Authorization: optional bearer token to authenticate
