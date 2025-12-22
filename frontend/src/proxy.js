import { NextResponse } from 'next/server';

export function proxy(request) {
  const path = request.nextUrl.pathname;
  const searchParams = request.nextUrl.searchParams;
  
  const adminLoginPath = '/putiroski';
  const adminDashboardPath = '/putiroski/dashboard';

  if (path.startsWith(adminLoginPath)) {
    
    const token = request.cookies.get('jwt')?.value;

    if (path === adminLoginPath) {
      
      if (token) {
        return NextResponse.redirect(new URL(adminDashboardPath, request.url));
      }

      const urlKey = searchParams.get('key');
      const secretKey = process.env.ADMIN_LOGIN_KEY;

      if (urlKey !== secretKey) {
        return NextResponse.rewrite(new URL('/404', request.url));
      }

      return NextResponse.next();
    }

    if (!token) {
      return NextResponse.redirect(new URL(adminLoginPath, request.url));
    }
  }

  return NextResponse.next();
}

export const config = {
  matcher: ['/putiroski/:path*'],
};