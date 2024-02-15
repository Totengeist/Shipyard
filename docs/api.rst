======================
API Reference
======================

.. note::

   This documentation is under construction and incomplete.

Reference information to help you get started integrating Shipyard
functionality into your application via our REST API.

**Note:** Bearer tokens listed as "optional" are only optional for
clients that support cookies.

User Management
---------------

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
   :reqheader Accept: the response content type depends on
                      :mailheader:`Accept` header
   :reqheader Authorization: optional OAuth token to authenticate
   :resheader Content-Type: this depends on :mailheader:`Accept`
                            header of request
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

   Information about the currently logged in user.
   
   The ``session_id`` token should be stored by the client and used as a
   Bearer token on future requests. Logging the user out shoudld be as simple
   as deleting the token in the client's storage, but you can also :http:get:`/api/v1/logout`.

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

.. http:post:: /api/v1/user/(user_id)
.. http:delete:: /api/v1/user/(user_id)
.. http:get:: /api/v1/logout
.. http:get:: /api/v1/me

   Information about the currently logged in user.

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

.. http:post:: /api/v1/user/(user_id)
.. http:delete:: /api/v1/user/(user_id)

General
-------

.. http:get:: /api/v1/version

Ship Management
---------------

.. http:get:: /api/v1/ship
.. http:get:: /api/v1/ship/(ref)
.. http:get:: /api/v1/ship/(ref)/download
.. http:post:: /api/v1/ship
.. http:post:: /api/v1/ship/(ref)
.. http:post:: /api/v1/ship/(ref)/upgrade
.. http:delete:: /api/v1/ship/(ref)

Save Management
---------------

.. http:get:: /api/v1/save
.. http:get:: /api/v1/save/(ref)
.. http:get:: /api/v1/save/(ref)/download
.. http:post:: /api/v1/save
.. http:post:: /api/v1/save/(ref)
.. http:delete:: /api/v1/save/(ref)

Mod Management
--------------

.. http:get:: /api/v1/modification
.. http:get:: /api/v1/modification/(ref)
.. http:post:: /api/v1/modification
.. http:post:: /api/v1/modification/(ref)
.. http:delete:: /api/v1/modification/(ref)

Screenshot Management
---------------------

.. http:get:: /api/v1/screenshots/(ship_ref)
.. http:get:: /api/v1/screenshot/(ref)
.. http:post:: /api/v1/screenshots/(ship_ref)
.. http:post:: /api/v1/screenshot/(ref)
.. http:delete:: /api/v1/screenshot/(ref)

Tag & Release Management
------------------------

.. http:get:: /api/v1/tag
.. http:get:: /api/v1/tag/(slug)
.. http:post:: /api/v1/tag
.. http:post:: /api/v1/tag/(slug)
.. http:delete:: /api/v1/tag/(slug)
.. http:get:: /api/v1/release
.. http:get:: /api/v1/release/(slug)
.. http:post:: /api/v1/release
.. http:post:: /api/v1/release/(slug)
.. http:delete:: /api/v1/release/(slug)

Permission & Role Management
----------------------------

.. http:get:: /api/v1/permission
.. http:post:: /api/v1/permission
.. http:get:: /api/v1/permission/(slug)
.. http:post:: /api/v1/permission/(slug)
.. http:delete:: /api/v1/permission/(slug)
.. http:get:: /api/v1/role
.. http:post:: /api/v1/role
.. http:get:: /api/v1/role/(slug)
.. http:post:: /api/v1/role/(slug)
.. http:delete:: /api/v1/role/(slug)