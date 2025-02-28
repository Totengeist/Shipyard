import { ItemInterface } from '../_types/item.interface';

export interface UserInterface {
    email: string,
    name: string|null,
    password?: string,
    password_confirmation?: string,
    ref: string,
    ships?: ItemInterface[],
    saves?: ItemInterface[],
    mods?: ItemInterface[],
}
