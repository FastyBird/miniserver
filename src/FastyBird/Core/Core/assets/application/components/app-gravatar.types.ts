export type AppGravatarRating = 'g' | 'pg' | 'r' | 'x';

export type AppGravatarDefaultImg = '404' | 'mp' | 'mm' | 'identicon' | 'monsterid' | 'wavatar' | 'retro' | 'robohash' | 'blank';

export interface IAppGravatarProps {
	email: string;
	hash?: string | null;
	size?: number;
	defaultImg?: AppGravatarDefaultImg;
	rating?: AppGravatarRating;
	alt?: string;
	protocol?: string | null;
	hostname?: string;
}
