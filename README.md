# Film Reader
<img src="https://img.shields.io/badge/dle-13.0+-007dad.svg"> <img src="https://img.shields.io/badge/lang-tr-ce600f.svg"> <img src="https://img.shields.io/badge/lang-en-ce600f.svg"> <img src="https://img.shields.io/badge/lang-ru-ce600f.svg"> <img src="https://img.shields.io/badge/license-MIT-60ce0f.svg">

Film Reader modülü, film sitesi sahipleri için büyük bir kolaylık sağlar. Plugin sistemi sayesinde tek kurulum ile her siteden bilgileri çekebilirsiniz. Hızlı bir şekilde hedef siteden bilgileri çekebilir ayrıca kapak resmini de sitenize yükleyip otomatik olarak filigran ekletebilirsiniz. Bu işlemi manuel yapacak olsanız en az 2-3 dk'nızı alacaktır, modül ile 4-5 sn de halledebilirsiniz. Çektiğiniz verilerde oynama yapmanız gerekiyorsa. Bu değişiklikleri belirtilen kurallar ile otomatik değişim kısmına girerek otomatik olarak yapılmasını sağlayabilirsiniz.
İlave alanlara çektiğiniz bilgileri aynı zamanda etiket olarak da kullanabilirsiniz. Hatta bu alanları link olarak kullanabilir ve linki özel olarak yazabilirsiniz. Örneğin: year ilave alanı için siteniz.com/yıl/2014 olarak erişimi mümkündür. Belirlediğiniz ilave alanlar için bu tür yazımlar belirterek SEF linkler elde edebilirsiniz.

## Kurulum
**1)** Eklentiyi zip olarak admin panelden yükleyebilirsiniz. Fakat DLE eklenti sistemindeki yükleme alanı Github'dan indirilen ziplerin direkt olarak yüklenmesi için uygun değil. Bu nedenle zip olarak modülü indirip, modülü bir dizine çıkartıp. XML dosyasının bulunduğu dizindeki tüm dosyaları seçerek zip oluşturun.

**2)** Ardından eklentiyi zip dosyası sitenize yükleyin.


## Konfigürasyon
Kurulum işlemi ardından modül yönetim paneline geliniz. Buradan modül için gerekli olan ilave alanları tek tıklama ile ekleyebilir, .htaccess dosyası için gerekli tanımlamaları da aynı şekilde ekleyebilirsiniz.

Bu işlemlerin ardından, modülün çalışması için [film-reader-plugins](https://github.com/dlenettr/film-reader-plugins) reposundaki pluginlerden istediklerinizi `engine/classes/mws-film/` klasörüne atmanız gereklidir.

Örneğin imdb.com'dan veri çekmek isterseniz:

* engine/classes/mws-film/imdb.class.php
* engine/classes/mws-film/plugins/imdb.plugin

Bu iki dosyanın sitenizde olması gereklidir. Diğer pluginler için de aynı yapı geçerlidir. Dilerseniz tüm pluginleri sitenize yükleyip sadece istediklerinizi kullanabilirsiniz.

Modül yönetim panelindeki "Pluginler" sekmesinden sadece kullanmak istediğiniz pluginleri aktifleştiriniz.

Her site veya farklı yapıdaki sayfalar için ayrı bir plugin gereklidir. imdb plugini ile çekilmiş bir veri içeriği:

```
    [img] => http://ia.media-imdb.com/images/M/MV5BMTQ2MzE2NTk0NF5BMl5BanBnXkFtZTgwOTM3NTk1MjE@._V1_UY1200_CR90,0,630,1200_AL_.jpg
    [namelong] => The Equalizer (2014)
    [name] => The Equalizer
    [year] => 2014
    [url] => http://www.imdb.com/title/tt0455944/
    [type] => video.movie
    [crating] => R
    [soundtracks] =>
    [runtime] => 2sa 12dk
    [genres] => Action, Crime, Thriller
    [ratinga] => 7,2
    [ratingb] => 10
    [ratingc] => 212,921
    [story] => In The Equalizer, Denzel Washington plays McCall, a man who believes he has put his mysterious past behind him and dedicated himself to beginning a new, quiet life. But when McCall meets Teri (Chloë Grace Moretz), a young girl under the control of ultra-violent Russian gangsters, he can't stand idly by - he has to help her. Armed with hidden skills that allow him to serve vengeance against anyone who would brutalize the helpless, McCall comes out of his self-imposed retirement and finds his desire for justice reawakened. If someone has a problem, if the odds are stacked against them, if they have nowhere else to turn, McCall will help. He is The Equalizer.
    [country] => USA
    [locations] => Haverhill, Massachusetts, USA
    [language] => English, Russian, Spanish
    [productionfirm] => Columbia Pictures,LStar Capital,Village Roadshow Pictures
    [datelocal] => 26 September 2014 (Turkey)
    [namelocal] => Adalet
    [color] => Color
    [sound] => SDDS, Datasat, Dolby Digital, Dolby Surround 7.1
    [budget] => $55,000,000(estimated)
    [aratio] => 2.35 : 1
    [tagline] => What do you see when you look at me?
    [orgimg] => http://ia.media-imdb.com/images/M/MV5BMTQ2MzE2NTk0NF5BMl5BanBnXkFtZTgwOTM3NTk1MjE@._V1.jpg
    [writers] => Richard Wenk, Michael Sloan
    [actors] => Denzel Washington, Marton Csokas, Chloë Grace Moretz
    [director] => Antoine Fuqua
```

## Ekran Görüntüleri
![Ekran 1](/docs/screen1.png?raw=true)
![Ekran 2](/docs/screen2.png?raw=true)
![Ekran 3](/docs/screen3.png?raw=true)

## Tarihçe

| Version | Tarih | Uyumluluk | Yenilikler |
| ------- | ----- | --------- | ---------- |
| **1.8.3** | 14.02.2019 | 13.0+ | İstek sistemi tamamen kaldırıldı |
| **1.8.2** | 15.11.2018 | 13.0+ | DLE 13.1 uyumluluğu sağlandı |
| **1.8.1** | 04.08.2018 | 13.0 | uploads klasörü için otomatik klasör oluşturma özelliği eklendi. |
| **1.8.0** | 02.08.2018 | 13.0 | DLE 13.0 uyumlu plugin haline getirildi. |
| **1.7.2** | 04.09.2017 | 12.0, 12.1 | DLE 12.0 için güncellendi. |
| **1.7.1** | 15.08.2017 | 11.3 | Yeni eklenen ilave alan resim sistemine uygun hale getirilmiştir. |
| **1.7.0** | 09.04.2017 | 11.2 | TinyMCE editörü için destek eklendi. Aktif editöre ekleneceği için önce editöre tıklamak gerekli.<br>Reyting değeri için virgül/nokta seçimi admin panelden değiştirilebili olarak ayarlandı.<br>IMDB ekran görüntüleri çekme hatası giderildi. |
| **1.6.9** | 11.12.2016 | 11.2 | IMDB resimlerini indirme sorunu giderildi. |
| **1.6.8** | 09.12.2016 | 11.2 | DLE 11.2 uyumluluğu kontrol edildi.<br>Plugin sisteminde rsz.io servisi için düzenleme yapıldı. |
| **1.6.7** | 05.10.2016 | 11.1 | İstek sistemi için günlük limit özelliği eklendi<br>Dil dosyalarındaki hata giderildi.
| **1.6.6** | 21.09.2016 | 11.1 | DLE 11.1 uyumluluğu sağlandı.<br>Plugin sisteminde düzenleme yapıldı. |
| **1.6.5** | 09.03.2016 | 11.0 | DLE 11.0 uyumluluğu sağlandı.<br>Ufak eklemeler ve düzenlemeler yapıldı.<br>Reyting image sistemi güvenlik açığı olabileceği gerekçesiyle kaldırıldı. |
| **1.6.4** | 28.01.2016 | 10.6 | Veri yazma işleminde kullanılan jаvascript metodu değiştirildi.<br>IMDB.com için resim çekme özelliği iyileştirildi.<br>Çekilen resimleri formatlarına göre kontrol etme özelliği eklendi.<br>Hatalı çekilen/çekilemeyen resimlerin yerine "Varsayılan kapak resim" ayarı eklendi. |
| **1.6.3** | 31.10.2015 | 10.6 | DLE 10.6 için güncelleme yapıldı.<br>IMDB.com için resim çekme özelliği admin panel için aktif edildi.<br>Youtube V3 sistemi için herkesin kendi API keyini girebileceği alan eklendi.<br>Hata mesajları için geliştirme yapıldı.<br>Özel ilave alanlar için plugin desteği eklendi.<br>Geliştiriciler için örnek bir plugin pakete dahil edildi. |
| **1.6.2** | 21.06.2015 | 10.5 | DLE 10.5 için güncelleme yapıldı. |
| **1.6.1** | 10.05.2015 | 10.4 | Değişen Youtube API sistemi nedeniyle tek dosyada güncelleme yapıldı. |
| **1.6.0** | 31.01.2015 | 10.4 | Siteye makale ekleme sayfasında trailer çekme özelliği eklendi.<br>Film etiketlerindeki nokta karakterinin silinmesi özelliği eklendi.<br>DLE 10.4 ile uyumluluk sağlandı<br>Tag sistemi yeniden yazıldı |
| **1.5.6** | 20.12.2014 | 10.3 | Taglardaki karakter hatası giderildi. Noktalı yazılar için destek eklendi.<br>Kurulum esnasındaki uyarı kaldırıldı.<br>Trailer okumak için zorunlu tutulan film_url değişkeni kaldırıldı. Başlığı yazıp direkt olarak trailer aratabilirsiniz. |
| **1.5.5** | 05.12.2014 | 10.3 | Kurulumda meydana gelen hata için düzeltmeler yapıldı<br>Eklenen kodlar kısaltılarak bir dosya da toplandı<br>Sadece admin panelde çalışan sistem artık site makale ekleme panelinde de çalışacak hale getirildi |
| **1.5.4** | 31.08.2014 | 10.3 | Birçok ufak hata giderildi. Kod optimizasyonu yapıldı.<br>Admin panelinde ve scriptte düzenlemeler yapıldı.<br>Site üzerinden kullanıcıların film ekleyebilmesi için fonksiyon eklendi.<br>DLE 10.3 ile uyumluluk sağlandı. |
| **1.5.2** | 13.04.2014 | 10.2 | DLE 10.2 Uyumluluğu sağlandı.<br>İstek sistemi pakete dahil edildi. |
| **1.5.1** | 08.02.2014 | 10.1 | Bilinen hatalar düzeltildi ve kodlarda iyileştirmeler yapıldı.<br>Eski sürümlere yönelik düzeltmeler yapıldı.<br>Otomatik kurulum sistemi dahil edildi. |
| **1.5.0** | 07.01.2014 | 10.1 | Etiket sistemi sayesinde SEO dostu linkler ile filmlerinizi listeleyin.<br>Admin panelinden yönetilebilir AJAX'lı film istek sistemi. (Ücretli - Opsiyonel)<br>Film önizlemeleri çekme ve resim sayısını belirleyebilme. (Plugine göre değişir)<br>Bilinen hatalar düzeltildi ve kodlarda iyileştirmeler yapıldı. |
| **1.4.0** | 22.08.2013 | 10.1 | Kapak resim için üzerine yazdırma,<br>Kenar uzunluğuna ve kenara göre yeniden boyutlandırma<br>Filigran ekleme ve boyutlandırma sıralaması ayarlama<br>Pluginler için büyük / küçük kapak resim kullanınımı. (Plugine göre değişir)<br>Trailer için video ID ya da Link kullanım seçeneği<br>Otomatik bul değiştir fonksiyonu için alanları belirleyebilme.<br>Ufak hatalar giderildi ve arayüzde değişiklikler yapıldı.<br>İlave alan kullanım seçeneklerine (tags) etiketler alanı eklendi |
