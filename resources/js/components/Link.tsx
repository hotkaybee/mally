import React from 'react';
import { Link as RouterLink } from 'react-router-dom';

export interface LinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
  href?: string;
  to?: string;
  children?: React.ReactNode;
}

export default function Link({ href, to, children, ...props }: LinkProps) {
  const target = to || href || '/';
  const isExternal = target.startsWith('http://') || target.startsWith('https://') || target.startsWith('tel:') || target.startsWith('mailto:');

  if (isExternal) {
    return (
      <a href={target} {...props}>
        {children}
      </a>
    );
  }

  return (
    <RouterLink to={target} {...(props as any)}>
      {children}
    </RouterLink>
  );
}

export { Link };
