import { SVGAttributes } from 'react';

export default function ApplicationLogo(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-label="Escada">
            <rect x="9" y="4" width="5" height="40" rx="2" />
            <rect x="34" y="4" width="5" height="40" rx="2" />
            <rect x="12" y="10" width="24" height="3" rx="1.5" />
            <rect x="12" y="18" width="24" height="3" rx="1.5" />
            <rect x="12" y="26" width="24" height="3" rx="1.5" />
            <rect x="12" y="34" width="24" height="3" rx="1.5" />
        </svg>
    );
}
