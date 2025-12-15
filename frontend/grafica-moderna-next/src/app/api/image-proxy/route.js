import { NextResponse } from 'next/server';

const BACKEND_URL = process.env.INTERNAL_API_URL || 'https://localhost:7255';

export async function GET(request) {
  const { searchParams } = new URL(request.url);
  const path = searchParams.get('path');

  if (!path) {
    return new NextResponse('Path parameter is required', { status: 400 });
  }

  const targetUrl = `${BACKEND_URL}${path.startsWith('/') ? '' : '/'}${path}`;

  try {
    
    const response = await fetch(targetUrl, {
      method: 'GET',
      headers: {
      },
      cache: 'force-cache' 
    });

    if (!response.ok) {
      console.error(`Erro ao buscar imagem: ${targetUrl} - Status: ${response.status}`);
      return new NextResponse('Image not found', { status: 404 });
    }

    const blob = await response.blob();
    const headers = new Headers();
    
    headers.set("Content-Type", response.headers.get("Content-Type") || "image/jpeg");
    headers.set("Cache-Control", "public, max-age=31536000, immutable");

    return new NextResponse(blob, { headers });

  } catch (error) {
    console.error("Erro no proxy de imagem:", error);
    return new NextResponse('Internal Server Error', { status: 500 });
  }
}