<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use Illuminate\Database\Seeder;

class BangladeshLocationSeeder extends Seeder
{
    /**
     * Seed Bangladesh with its divisions, districts, and upazilas.
     */
    public function run(): void
    {
        $country = Country::firstOrCreate(
            ['name' => 'Bangladesh'],
            ['status' => 'active'],
        );

        foreach ($this->locations() as $divisionName => $districts) {
            $division = Division::firstOrCreate(
                ['country_id' => $country->id, 'name' => $divisionName],
                ['status' => 'active'],
            );

            foreach ($districts as $districtName => $upazilas) {
                $district = District::firstOrCreate(
                    ['division_id' => $division->id, 'name' => $districtName],
                    ['country_id' => $country->id, 'status' => 'active'],
                );

                foreach ($upazilas as $upazilaName) {
                    Upazila::firstOrCreate(
                        ['district_id' => $district->id, 'name' => $upazilaName],
                        ['status' => 'active'],
                    );
                }
            }
        }
    }

    /**
     * Bangladesh's 8 divisions, 64 districts, and their upazilas/thanas.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function locations(): array
    {
        return [
            'Barisal' => [
                'Jhalakathi' => ['Jhalakathi Sadar', 'Kathalia', 'Nalchity', 'Rajapur'],
                'Patuakhali' => ['Bauphal', 'Dashmina', 'Dumki', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Patuakhali Sadar', 'Rangabali'],
                'Pirojpur' => ['Bhandaria', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad', 'Pirojpur Sadar', 'Zianagar'],
                'Barisal' => ['Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Barisal Sadar', 'Gournadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur'],
                'Bhola' => ['Bhola Sadar', 'Borhanuddin', 'Charfesson', 'Doulatkhan', 'Lalmohan', 'Monpura', 'Tazumuddin'],
                'Barguna' => ['Amtali', 'Bamna', 'Barguna Sadar', 'Betagi', 'Pathorghata', 'Taltali'],
            ],
            'Chattogram' => [
                'Comilla' => ['Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram', 'Comilla Sadar', 'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Lalmai', 'Meghna', 'Monohargonj', 'Muradnagar', 'Nangalkot', 'Sadar South', 'Titas'],
                'Feni' => ['Chhagalnaiya', 'Daganbhuiyan', 'Feni Sadar', 'Fulgazi', 'Parshuram', 'Sonagazi'],
                'Brahmanbaria' => ['Akhaura', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Brahmanbaria Sadar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail'],
                'Rangamati' => ['Baghaichari', 'Barkal', 'Belaichari', 'Juraichari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali', 'Rangamati Sadar'],
                'Noakhali' => ['Begumganj', 'Chatkhil', 'Companiganj', 'Hatia', 'Kabirhat', 'Noakhali Sadar', 'Senbagh', 'Sonaimuri', 'Subarnachar'],
                'Chandpur' => ['Chandpur Sadar', 'Faridgonj', 'Haimchar', 'Hajiganj', 'Kachua', 'Matlab North', 'Matlab South', 'Shahrasti'],
                'Lakshmipur' => ['Kamalnagar', 'Lakshmipur Sadar', 'Raipur', 'Ramganj', 'Ramgati'],
                'Chattogram' => ['Anwara', 'Banshkhali', 'Boalkhali', 'Chandanaish', 'Fatikchhari', 'Hathazari', 'Karnafuli', 'Lohagara', 'Mirsharai', 'Patiya', 'Rangunia', 'Raozan', 'Sandwip', 'Satkania', 'Sitakunda'],
                "Cox's Bazar" => ['Chakaria', "Cox's Bazar Sadar", 'Eidgaon', 'Kutubdia', 'Moheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhiya'],
                'Khagrachhari' => ['Dighinala', 'Guimara', 'Khagrachhari Sadar', 'Laxmichhari', 'Manikchari', 'Matiranga', 'Mohalchari', 'Panchari', 'Ramgarh'],
                'Bandarban' => ['Alikadam', 'Bandarban Sadar', 'Lama', 'Naikhongchhari', 'Rowangchhari', 'Ruma', 'Thanchi'],
            ],
            'Dhaka' => [
                'Narsingdi' => ['Belabo', 'Monohardi', 'Narsingdi Sadar', 'Palash', 'Raipura', 'Shibpur'],
                'Gazipur' => ['Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Sreepur'],
                'Shariatpur' => ['Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Shariatpur Sadar', 'Zajira'],
                'Narayanganj' => ['Araihazar', 'Bandar', 'Narayanganj Sadar', 'Rupganj', 'Sonargaon'],
                'Tangail' => ['Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur', 'Tangail Sadar'],
                'Kishoreganj' => ['Austagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimgonj', 'Katiadi', 'Kishoreganj Sadar', 'Kuliarchar', 'Mithamoin', 'Nikli', 'Pakundia', 'Tarail'],
                'Manikganj' => ['Doulatpur', 'Gior', 'Harirampur', 'Manikganj Sadar', 'Saturia', 'Shibaloy', 'Singiar'],
                'Dhaka' => ['Dhamrai', 'Dohar', 'Keraniganj', 'Nawabganj', 'Savar'],
                'Munshiganj' => ['Gajaria', 'Lohajang', 'Munshiganj Sadar', 'Sirajdikhan', 'Sreenagar', 'Tongibari'],
                'Rajbari' => ['Baliakandi', 'Goalanda', 'Kalukhali', 'Pangsa', 'Rajbari Sadar'],
                'Madaripur' => ['Dasar', 'Kalkini', 'Madaripur Sadar', 'Rajoir', 'Shibchar'],
                'Gopalganj' => ['Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara'],
                'Faridpur' => ['Alfadanga', 'Bhanga', 'Boalmari', 'Charbhadrasan', 'Faridpur Sadar', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha'],
            ],
            'Khulna' => [
                'Jashore' => ['Abhaynagar', 'Bagherpara', 'Chougachha', 'Jashore Sadar', 'Jhikargacha', 'Keshabpur', 'Manirampur', 'Sharsha'],
                'Satkhira' => ['Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Satkhira Sadar', 'Shyamnagar', 'Tala'],
                'Meherpur' => ['Gangni', 'Meherpur Sadar', 'Mujibnagar'],
                'Narail' => ['Kalia', 'Lohagara', 'Narail Sadar'],
                'Chuadanga' => ['Alamdanga', 'Chuadanga Sadar', 'Damurhuda', 'Jibannagar'],
                'Kushtia' => ['Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Kushtia Sadar', 'Mirpur'],
                'Magura' => ['Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur'],
                'Khulna' => ['Botiaghata', 'Dakop', 'Digholia', 'Dumuria', 'Fultola', 'Koyra', 'Paikgasa', 'Rupsha', 'Terokhada'],
                'Bagerhat' => ['Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola'],
                'Jhenaidah' => ['Harinakundu', 'Jhenaidah Sadar', 'Kaliganj', 'Kotchandpur', 'Moheshpur', 'Shailkupa'],
            ],
            'Mymensingh' => [
                'Sherpur' => ['Jhenaigati', 'Nalitabari', 'Nokla', 'Sherpur Sadar', 'Sreebordi'],
                'Mymensingh' => ['Bhaluka', 'Dhobaura', 'Fulbaria', 'Gafargaon', 'Gouripur', 'Haluaghat', 'Iswarganj', 'Muktagacha', 'Mymensingh Sadar', 'Nandail', 'Phulpur', 'Tarakanda', 'Trishal'],
                'Jamalpur' => ['Bokshiganj', 'Dewangonj', 'Islampur', 'Jamalpur Sadar', 'Madarganj', 'Melandah', 'Sarishabari'],
                'Netrokona' => ['Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Khaliajuri', 'Madan', 'Mohongonj', 'Netrokona Sadar', 'Purbadhala'],
            ],
            'Rajshahi' => [
                'Sirajganj' => ['Belkuchi', 'Chauhali', 'Kamarkhand', 'Kazipur', 'Raigonj', 'Shahjadpur', 'Sirajganj Sadar', 'Tarash', 'Ullapara'],
                'Pabna' => ['Atghoria', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishurdi', 'Pabna Sadar', 'Santhia', 'Sujanagar'],
                'Bogura' => ['Adamdighi', 'Bogra Sadar', 'Dhunot', 'Dupchanchia', 'Gabtali', 'Kahaloo', 'Nondigram', 'Shajahanpur', 'Shariakandi', 'Sherpur', 'Shibganj', 'Sonatala'],
                'Rajshahi' => ['Bagha', 'Bagmara', 'Charghat', 'Durgapur', 'Godagari', 'Mohonpur', 'Paba', 'Puthia', 'Tanore'],
                'Natore' => ['Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Naldanga', 'Natore Sadar', 'Singra'],
                'Joypurhat' => ['Akkelpur', 'Joypurhat Sadar', 'Kalai', 'Khetlal', 'Panchbibi'],
                'Chapainawabganj' => ['Bholahat', 'Chapainawabganj Sadar', 'Gomostapur', 'Nachol', 'Shibganj'],
                'Naogaon' => ['Atrai', 'Badalgachi', 'Dhamoirhat', 'Manda', 'Mohadevpur', 'Naogaon Sadar', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar'],
            ],
            'Rangpur' => [
                'Panchagarh' => ['Atwari', 'Boda', 'Debiganj', 'Panchagarh Sadar', 'Tetulia'],
                'Dinajpur' => ['Birampur', 'Birganj', 'Birol', 'Bochaganj', 'Chirirbandar', 'Dinajpur Sadar', 'Fulbari', 'Ghoraghat', 'Hakimpur', 'Kaharol', 'Khansama', 'Nawabganj', 'Parbatipur'],
                'Lalmonirhat' => ['Aditmari', 'Hatibandha', 'Kaliganj', 'Lalmonirhat Sadar', 'Patgram'],
                'Nilphamari' => ['Dimla', 'Domar', 'Jaldhaka', 'Kishorganj', 'Nilphamari Sadar', 'Syedpur'],
                'Gaibandha' => ['Gaibandha Sadar', 'Gobindaganj', 'Palashbari', 'Phulchari', 'Sadullapur', 'Saghata', 'Sundarganj'],
                'Thakurgaon' => ['Baliadangi', 'Haripur', 'Pirganj', 'Ranisankail', 'Thakurgaon Sadar'],
                'Rangpur' => ['Badargonj', 'Gangachara', 'Kaunia', 'Mithapukur', 'Pirgacha', 'Pirgonj', 'Rangpur Sadar', 'Taragonj'],
                'Kurigram' => ['Bhurungamari', 'Charrajibpur', 'Chilmari', 'Kurigram Sadar', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Rowmari', 'Ulipur'],
            ],
            'Sylhet' => [
                'Sylhet' => ['Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Dakshinsurma', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmaninagar', 'Sylhet Sadar', 'Zakiganj'],
                'Moulvibazar' => ['Barlekha', 'Juri', 'Kamolganj', 'Kulaura', 'Moulvibazar Sadar', 'Rajnagar', 'Sreemangal'],
                'Habiganj' => ['Ajmiriganj', 'Bahubal', 'Baniachong', 'Chunarughat', 'Habiganj Sadar', 'Lakhai', 'Madhabpur', 'Nabiganj'],
                'Sunamganj' => ['Bishwambarpur', 'Chhatak', 'Derai', 'Dharmapasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Madhyanagar', 'Shalla', 'South Sunamganj', 'Sunamganj Sadar', 'Tahirpur'],
            ],
        ];
    }
}
