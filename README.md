# The Last Starship Shipyard
[![Build Status](https://github.com/Totengeist/Shipyard/actions/workflows/tests.yml/badge.svg)](https://github.com/Totengeist/Shipyard/actions/workflows/tests.yml) [![codecov](https://codecov.io/gh/Totengeist/Shipyard/branch/main/graph/badge.svg?token=ECJQYDJRPX)](https://codecov.io/gh/Totengeist/Shipyard) [![Documentation Status](https://readthedocs.org/projects/shipyard/badge/?version=latest)](https://shipyard.readthedocs.io/en/latest/?badge=latest)

Shipyard is a sharing system for player created ships, saves and mods for the Introversion game [The Last Starship][1].

The [live website][6] is managed by the community and is run by the [community wiki][4] staff. We are not affiliated with Introversion Software.

The backend is developed on the [Slim][2] framework and the frontend is developed on the [Angular][3] framework.

## Installation

1. Download the latest [release archive][7].
2. Extract it to the desired location on your server.
3. Create the database you intend to use. It should not have any tables.
3. Update your environment variables or the `.env` file.

   **Note:** It is recommended to set the `STORAGE` and `DEBUG_FILE` variables to a directory outside of the project directory. This facilitates easy updates without affecting your storage and logs. The `BASE_URL` variable can be used when Shipyard is in a subfolder (i.e. example.com/shipyard).

3. Point your webserver at the `src/public` directory. (From here, we'll use `https://example.com` as the root of Shipyard.)
4. Navigate to `https://example.com/install/` to run the initial migration.
5. **Delete the install directory.** Shipyard is in an early stage of development and doesn't have a proper installer yet. **Leaving the installer in place could allow an attacker to reset the database.**
6. Login to the administrator account and set the email address and password. The default login credentials are:

   Username: `admin@tls-wiki.com`
   Password: `secret`

## Update

**Note:** Shipyard is still in an early stage of development and may see some drastic changes until it becomes stable.

1. Backup your database, as well as the `storage` directory and `debug.log` file.
2. Download the latest [release archive][7].
3. Delete the existing Shipyard file structure. Extract the release archive to the same location.
4. Navigate to `https://example.com/install/` to run the update migration(s).
5. **Delete the install directory.** Shipyard is in an early stage of development and doesn't have a proper installer yet. **Leaving the installer in place could allow an attacker to reset the database.**
6. Replace the `storage` directory and `debug.log` file if necessary.

## Support

For questions not related to contributing directly to the project, please reach out on [Discord][5].

## Contributing

Pull requests are welcome. For major changes, please [open an issue][8] first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

[MIT](./LICENSE)

 [1]: https://steamcommunity.com/app/1857080
 [2]: https://www.slimframework.com/
 [3]: https://angular.io/
 [4]: https://www.tls-wiki.com/
 [5]: https://discord.gg/AcCgj3T5sH
 [6]: https://shipyard.tls-wiki.com
 [7]: https://github.com/Totengeist/Shipyard/releases
 [8]: https://github.com/Totengeist/Shipyard/issues/new/choose
