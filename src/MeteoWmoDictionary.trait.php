<?php namespace ProcessWire;

/**
 * Meteo — WMO Weather Code Dictionary
 *
 * Includes translated labels (15 languages), icon slugs, and wind direction helper.
 */
trait MeteoWmoDictionary {

    protected static function wmoLabel(int $code, string $lang = 'en'): string {
        static $cache = [];
        $key = $code . '_' . $lang;
        if (isset($cache[$key])) return $cache[$key];

        $codes = self::wmoDictionary();
        $row = $codes[$code] ?? $codes[0];
        return $cache[$key] = $row[$lang] ?? $row['en'];
    }

    protected static function wmoIcon(int $code): string {
        if ($code === 0)  return 'clear';
        if ($code === 1)  return 'mostly-clear';
        if ($code === 2)  return 'partly-cloudy';
        if ($code === 3)  return 'overcast';
        if ($code <= 48)  return 'fog';
        if ($code <= 55)  return 'drizzle';
        if ($code <= 61)  return 'rain';
        if ($code === 65) return 'rain-heavy';
        if ($code <= 68)  return 'rain-heavy';
        if ($code <= 77)  return 'snow';
        if ($code === 82) return 'showers-heavy';
        if ($code <= 82)  return 'showers';
        if ($code <= 86)  return 'snow-showers';
        if ($code >= 95)  return 'thunderstorm';
        return 'unknown';
    }

    protected static function windDir(float $deg): string {
        return ['N','NE','E','SE','S','SW','W','NW'][(int)(round($deg / 45) % 8)];
    }

    private static function wmoDictionary(): array {
        return [
            0  => ['en'=>'Clear sky','ru'=>'Ясно','de'=>'Klarer Himmel','fr'=>'Ciel dégagé','es'=>'Cielo despejado','pt'=>'Céu limpo','it'=>'Cielo sereno','ja'=>'快晴','zh'=>'晴天','ko'=>'맑음','nl'=>'Helder','pl'=>'Bezchmurnie','uk'=>'Ясно','tr'=>'Açık','ar'=>'سماء صافية'],
            1  => ['en'=>'Mainly clear','ru'=>'Преимущественно ясно','de'=>'Überwiegend klar','fr'=>'Principalement dégagé','es'=>'Mayormente despejado','pt'=>'Principalmente limpo','it'=>'Principalmente sereno','ja'=>'概ね晴れ','zh'=>'大体晴天','ko'=>'주로 맑음','nl'=>'Overwegend helder','pl'=>'Głównie bezchmurnie','uk'=>'Переважно ясно','tr'=>'Çoğunlukla açık','ar'=>'صافٍ في معظمه'],
            2  => ['en'=>'Partly cloudy','ru'=>'Переменная облачность','de'=>'Teilweise bewölkt','fr'=>'Partiellement nuageux','es'=>'Parcialmente nublado','pt'=>'Parcialmente nublado','it'=>'Parzialmente nuvoloso','ja'=>'一部曇り','zh'=>'局部多云','ko'=>'부분 흐림','nl'=>'Gedeeltelijk bewolkt','pl'=>'Częściowe zachmurzenie','uk'=>'Мінлива хмарність','tr'=>'Parçalı bulutlu','ar'=>'غيوم جزئية'],
            3  => ['en'=>'Overcast','ru'=>'Пасмурно','de'=>'Bedeckt','fr'=>'Couvert','es'=>'Nublado','pt'=>'Nublado','it'=>'Coperto','ja'=>'曇り','zh'=>'阴天','ko'=>'흐림','nl'=>'Bewolkt','pl'=>'Pochmurno','uk'=>'Похмуро','tr'=>'Kapalı','ar'=>'غائم'],
            45 => ['en'=>'Foggy','ru'=>'Туман','de'=>'Neblig','fr'=>'Brouillard','es'=>'Niebla','pt'=>'Nevoeiro','it'=>'Nebbioso','ja'=>'霧','zh'=>'雾','ko'=>'안개','nl'=>'Mistig','pl'=>'Mgła','uk'=>'Туман','tr'=>'Sisli','ar'=>'ضبابي'],
            48 => ['en'=>'Icy fog','ru'=>'Изморозь','de'=>'Eisiger Nebel','fr'=>'Brouillard givrant','es'=>'Niebla helada','pt'=>'Nevoeiro gelado','it'=>'Nebbia gelida','ja'=>'着氷霧','zh'=>'冻雾','ko'=>'결빙 안개','nl'=>'IJsmist','pl'=>'Mroźna mgła','uk'=>'Крижаний туман','tr'=>'Buzlu sis','ar'=>'ضباب جليدي'],
            51 => ['en'=>'Light drizzle','ru'=>'Лёгкая морось','de'=>'Leichter Nieselregen','fr'=>'Bruine légère','es'=>'Llovizna ligera','pt'=>'Chuvisco leve','it'=>'Pioggerellina leggera','ja'=>'小雨','zh'=>'小毛雨','ko'=>'가벼운 이슬비','nl'=>'Lichte motregen','pl'=>'Lekka mżawka','uk'=>'Легкий дощ','tr'=>'Hafif çisenti','ar'=>'رذاذ خفيف'],
            53 => ['en'=>'Drizzle','ru'=>'Морось','de'=>'Nieselregen','fr'=>'Bruine','es'=>'Llovizna','pt'=>'Chuvisco','it'=>'Pioggerellina','ja'=>'霧雨','zh'=>'毛毛雨','ko'=>'이슬비','nl'=>'Motregen','pl'=>'Mżawka','uk'=>'Мряка','tr'=>'Çisenti','ar'=>'رذاذ'],
            55 => ['en'=>'Dense drizzle','ru'=>'Сильная морось','de'=>'Starker Nieselregen','fr'=>'Bruine dense','es'=>'Llovizna densa','pt'=>'Chuvisco denso','it'=>'Pioggerellina fitta','ja'=>'濃い霧雨','zh'=>'浓毛毛雨','ko'=>'짙은 이슬비','nl'=>'Dichte motregen','pl'=>'Gęsta mżawka','uk'=>'Густа мряка','tr'=>'Yoğun çisenti','ar'=>'رذاذ كثيف'],
            61 => ['en'=>'Light rain','ru'=>'Небольшой дождь','de'=>'Leichter Regen','fr'=>'Pluie légère','es'=>'Lluvia ligera','pt'=>'Chuva leve','it'=>'Pioggia leggera','ja'=>'小雨','zh'=>'小雨','ko'=>'가벼운 비','nl'=>'Lichte regen','pl'=>'Lekki deszcz','uk'=>'Легкий дощ','tr'=>'Hafif yağmur','ar'=>'مطر خفيف'],
            63 => ['en'=>'Moderate rain','ru'=>'Умеренный дождь','de'=>'Mäßiger Regen','fr'=>'Pluie modérée','es'=>'Lluvia moderada','pt'=>'Chuva moderada','it'=>'Pioggia moderata','ja'=>'中程度の雨','zh'=>'中雨','ko'=>'보통 비','nl'=>'Matige regen','pl'=>'Umiarkowany deszcz','uk'=>'Помірний дощ','tr'=>'Orta yağmur','ar'=>'مطر معتدل'],
            65 => ['en'=>'Heavy rain','ru'=>'Сильный дождь','de'=>'Starker Regen','fr'=>'Pluie forte','es'=>'Lluvia fuerte','pt'=>'Chuva forte','it'=>'Pioggia forte','ja'=>'大雨','zh'=>'大雨','ko'=>'강한 비','nl'=>'Zware regen','pl'=>'Silny deszcz','uk'=>'Сильний дощ','tr'=>'Yoğun yağmur','ar'=>'مطر غزير'],
            67 => ['en'=>'Freezing rain','ru'=>'Ледяной дождь','de'=>'Gefrierender Regen','fr'=>'Pluie verglaçante','es'=>'Lluvia helada','pt'=>'Chuva gelada','it'=>'Pioggia gelata','ja'=>'着氷雨','zh'=>'冻雨','ko'=>'어는 비','nl'=>'Ijzelregen','pl'=>'Marznący deszcz','uk'=>'Крижаний дощ','tr'=>'Dondurucu yağmur','ar'=>'مطر متجمد'],
            68 => ['en'=>'Sleet','ru'=>'Мокрый снег','de'=>'Graupel','fr'=>'Grésil','es'=>'Aguanieve','pt'=>'Granizo miúdo','it'=>'Nevischio','ja'=>'みぞれ','zh'=>'雨夹雪','ko'=>'진눈깨비','nl'=>'Ijzel','pl'=>'Deszcz ze śniegiem','uk'=>'Мокрий сніг','tr'=>'Sulu kar','ar'=>'زخة مطر ثلجية'],
            71 => ['en'=>'Light snow','ru'=>'Небольшой снег','de'=>'Leichter Schnee','fr'=>'Neige légère','es'=>'Nieve ligera','pt'=>'Neve leve','it'=>'Neve leggera','ja'=>'小雪','zh'=>'小雪','ko'=>'가벼운 눈','nl'=>'Lichte sneeuw','pl'=>'Lekki śnieg','uk'=>'Легкий сніг','tr'=>'Hafif kar','ar'=>'ثلج خفيف'],
            73 => ['en'=>'Moderate snow','ru'=>'Умеренный снег','de'=>'Mäßiger Schnee','fr'=>'Neige modérée','es'=>'Nieve moderada','pt'=>'Neve moderada','it'=>'Neve moderata','ja'=>'中程度の雪','zh'=>'中雪','ko'=>'보통 눈','nl'=>'Matige sneeuw','pl'=>'Umiarkowany śnieg','uk'=>'Помірний сніг','tr'=>'Orta kar','ar'=>'ثلج معتدل'],
            75 => ['en'=>'Heavy snow','ru'=>'Сильный снег','de'=>'Starker Schnee','fr'=>'Neige forte','es'=>'Nieve fuerte','pt'=>'Neve forte','it'=>'Neve forte','ja'=>'大雪','zh'=>'大雪','ko'=>'강한 눈','nl'=>'Zware sneeuw','pl'=>'Silny śnieg','uk'=>'Сильний сніг','tr'=>'Yoğun kar','ar'=>'ثلج كثيف'],
            77 => ['en'=>'Snow grains','ru'=>'Снежная крупа','de'=>'Schneekörner','fr'=>'Grains de neige','es'=>'Granizo de nieve','pt'=>'Grãos de neve','it'=>'Granelli di neve','ja'=>'霙','zh'=>'雪粒','ko'=>'싸라기눈','nl'=>'Sneeuwkorrels','pl'=>'Ziarna śniegu','uk'=>'Снігова крупа','tr'=>'Kar taneleri','ar'=>'حبيبات ثلج'],
            80 => ['en'=>'Slight showers','ru'=>'Слабый ливень','de'=>'Leichte Schauer','fr'=>'Averses légères','es'=>'Chubascos ligeros','pt'=>'Pancadas fracas','it'=>'Rovesci leggeri','ja'=>'にわか雨','zh'=>'小阵雨','ko'=>'약한 소나기','nl'=>'Lichte buien','pl'=>'Słaby przelotny deszcz','uk'=>'Слабка злива','tr'=>'Hafif sağanak','ar'=>'زخات خفيفة'],
            81 => ['en'=>'Moderate showers','ru'=>'Умеренный ливень','de'=>'Mäßige Schauer','fr'=>'Averses modérées','es'=>'Chubascos moderados','pt'=>'Pancadas moderadas','it'=>'Rovesci moderati','ja'=>'中程度のにわか雨','zh'=>'中阵雨','ko'=>'보통 소나기','nl'=>'Matige buien','pl'=>'Umiarkowany przelotny deszcz','uk'=>'Помірна злива','tr'=>'Orta sağanak','ar'=>'زخات معتدلة'],
            82 => ['en'=>'Heavy showers','ru'=>'Сильный ливень','de'=>'Starke Schauer','fr'=>'Averses fortes','es'=>'Chubascos fuertes','pt'=>'Pancadas fortes','it'=>'Rovesci forti','ja'=>'激しいにわか雨','zh'=>'大阵雨','ko'=>'강한 소나기','nl'=>'Zware buien','pl'=>'Silny przelotny deszcz','uk'=>'Сильна злива','tr'=>'Şiddetli sağanak','ar'=>'زخات غزيرة'],
            85 => ['en'=>'Snow showers','ru'=>'Снеговой ливень','de'=>'Schneeschauer','fr'=>'Averses de neige','es'=>'Chubascos de nieve','pt'=>'Pancadas de neve','it'=>'Rovesci di neve','ja'=>'雪のにわか雨','zh'=>'阵雪','ko'=>'눈 소나기','nl'=>'Sneeuwbuien','pl'=>'Opady śniegu','uk'=>'Снігопади','tr'=>'Kar sağanağı','ar'=>'زخات ثلجية'],
            86 => ['en'=>'Heavy snow showers','ru'=>'Сильные снеговые ливни','de'=>'Starke Schneeschauer','fr'=>'Averses de neige fortes','es'=>'Chubascos de nieve fuertes','pt'=>'Pancadas fortes de neve','it'=>'Forti rovesci di neve','ja'=>'激しい雪のにわか雨','zh'=>'强阵雪','ko'=>'강한 눈 소나기','nl'=>'Zware sneeuwbuien','pl'=>'Silne opady śniegu','uk'=>'Сильні снігопади','tr'=>'Yoğun kar sağanağı','ar'=>'زخات ثلجية كثيفة'],
            95 => ['en'=>'Thunderstorm','ru'=>'Гроза','de'=>'Gewitter','fr'=>'Orage','es'=>'Tormenta eléctrica','pt'=>'Trovoada','it'=>'Temporale','ja'=>'雷雨','zh'=>'雷暴','ko'=>'뇌우','nl'=>'Onweer','pl'=>'Burza','uk'=>'Гроза','tr'=>'Gök gürültülü fırtına','ar'=>'عاصفة رعدية'],
            96 => ['en'=>'Thunderstorm with hail','ru'=>'Гроза с градом','de'=>'Gewitter mit Hagel','fr'=>'Orage avec grêle','es'=>'Tormenta con granizo','pt'=>'Trovoada com granizo','it'=>'Temporale con grandine','ja'=>'雹を伴う雷雨','zh'=>'带冰雹的雷暴','ko'=>'우박 동반 뇌우','nl'=>'Onweer met hagel','pl'=>'Burza z gradem','uk'=>'Гроза з градом','tr'=>'Dolu eşlikli gök gürültüsü','ar'=>'عاصفة رعدية مع برد'],
            99 => ['en'=>'Thunderstorm with hail','ru'=>'Гроза с градом','de'=>'Gewitter mit Hagel','fr'=>'Orage avec grêle','es'=>'Tormenta con granizo','pt'=>'Trovoada com granizo','it'=>'Temporale con grandine','ja'=>'雹を伴う雷雨','zh'=>'带冰雹的雷暴','ko'=>'우박 동반 뇌우','nl'=>'Onweer met hagel','pl'=>'Burza z gradem','uk'=>'Гроза з градом','tr'=>'Dolu eşlikli gök gürültüsü','ar'=>'عاصفة رعدية مع برد'],
        ];
    }
}
