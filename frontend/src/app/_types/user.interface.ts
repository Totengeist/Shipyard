import { ItemInterface } from '../_types/item.interface';
import { RoleInterface } from '../_types/role.interface';

export interface UserInterface {
    email: string,
    name: string|null,
    password?: string,
    password_confirmation?: string,
    ref: string,
    ships?: ItemInterface[],
    saves?: ItemInterface[],
    mods?: ItemInterface[],
    hasSteamLogin?: boolean,
    hasDiscordLogin?: boolean,
    roles?: RoleInterface[],
}
