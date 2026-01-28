
'use client';

import React, { useState, useEffect } from 'react';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import Store from '@/components/Store';
import { useRouter } from 'next/navigation';

export default function TiendaPage() {
    const [isScrolled, setIsScrolled] = useState(false);
    const router = useRouter();

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 50);
        };
        window.addEventListener('scroll', handleScroll);
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    return (
        <div className="min-h-screen flex flex-col bg-vibrant-light">
            <Navbar isScrolled={isScrolled} />

            <main className="flex-grow pt-24 pb-20">
                <Store onBack={() => router.push('/')} />
            </main>

            <Footer />
        </div>
    );
}
