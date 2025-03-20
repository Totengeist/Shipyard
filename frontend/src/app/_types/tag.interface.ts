import { ItemInterface } from '../_types/item.interface';

export interface TagInterface {
    slug: string,
    label: string,
    description: string|null,
    locked?: number,
    ships?: ItemInterface[],
    saves?: ItemInterface[],
    mods?: ItemInterface[],
}
