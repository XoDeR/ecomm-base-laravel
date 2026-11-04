import { Store } from 'lucide-react';
import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return <Store {...(props as object)} />;
}
