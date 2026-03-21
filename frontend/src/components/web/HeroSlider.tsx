import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi,
} from '@/components/ui/carousel';
import { Button } from '@/components/ui/button';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface Slide {
  id: string;
  title: string;
  subtitle?: string;
  ctaText: string;
  ctaHref: string;
  gradient: string;
}

const slides: Slide[] = [
  {
    id: '1',
    title: 'Свежая выпечка каждый день',
    subtitle: 'Печём с душой из отборной муки',
    ctaText: 'В каталог',
    ctaHref: '/#products',
    gradient: 'from-amber-900/90 via-amber-800/80 to-orange-900/90',
  },
  {
    id: '2',
    title: 'Быстрая доставка',
    subtitle: 'Доставим за 1–2 часа по городу',
    ctaText: 'Заказать',
    ctaHref: '/cart',
    gradient: 'from-emerald-900/90 via-teal-800/80 to-cyan-900/90',
  },
  {
    id: '3',
    title: 'Акции и скидки',
    subtitle: 'Специальные предложения для вас',
    ctaText: 'Смотреть',
    ctaHref: '/#products',
    gradient: 'from-rose-900/90 via-pink-800/80 to-fuchsia-900/90',
  },
];

export function HeroSlider() {
  const [api, setApi] = useState<CarouselApi>();
  const [current, setCurrent] = useState(0);

  useEffect(() => {
    if (!api) return;
    setCurrent(api.selectedScrollSnap());
    api.on('select', () => setCurrent(api.selectedScrollSnap()));
  }, [api]);

  useEffect(() => {
    if (!api) return;
    const interval = setInterval(() => {
      const next = (api.selectedScrollSnap() + 1) % slides.length;
      api.scrollTo(next);
    }, 5000);
    return () => clearInterval(interval);
  }, [api]);

  return (
    <section className="relative w-full overflow-hidden">
      <Carousel setApi={setApi} opts={{ loop: true, align: 'start' }} className="w-full">
        <CarouselContent className="-ml-0">
          {slides.map((slide) => (
            <CarouselItem key={slide.id} className="pl-0">
              <div
                className={`
                  relative flex min-h-[320px] md:min-h-[400px] lg:min-h-[480px] 
                  items-center justify-center px-6 py-16 
                  bg-gradient-to-br ${slide.gradient}
                  rounded-none
                `}
              >
                <div className="container mx-auto flex flex-col items-start text-white">
                  <h2 className="max-w-2xl text-3xl font-bold tracking-tight md:text-4xl lg:text-5xl">
                    {slide.title}
                  </h2>
                  {slide.subtitle && (
                    <p className="mt-3 text-lg text-white/90 md:text-xl">
                      {slide.subtitle}
                    </p>
                  )}
                  <Button
                    asChild
                    size="lg"
                    className="mt-6 bg-white text-amber-900 hover:bg-white/90"
                  >
                    <Link to={slide.ctaHref}>
                      {slide.ctaText}
                      <ChevronRight className="ml-1 h-4 w-4" />
                    </Link>
                  </Button>
                </div>
              </div>
            </CarouselItem>
          ))}
        </CarouselContent>
      </Carousel>

      {/* Dots */}
      <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
        {slides.map((_, i) => (
          <button
            key={i}
            onClick={() => api?.scrollTo(i)}
            className={`h-2 rounded-full transition-all ${
              i === current ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/70'
            }`}
            aria-label={`Перейти к слайду ${i + 1}`}
          />
        ))}
      </div>
    </section>
  );
}
