<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $department = DB::table('departments')->first();

        $users = [
            // ICT =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Ruby Cabuhat',
                'email' => 'rscabuhat@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Helpdesk',
                'name' => 'Jasmine Leyva',
                'email' => 'icthelpdesk@leoniogroup.com',
                'position' => 'Helpdesk Support',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Jose Malgapo',
                'email' => 'jfmalgapo@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Benjie Abalon',
                'email' => 'bdabalon@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Technician',
                'name' => 'Stephen Kyle Donida',
                'email' => 'sjdonida@leoniogroup.com',
                'position' => 'IT Technician',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Steven Tablanza',
                'email' => 'setablanza@leoniogroup.com',
                'position' => 'ICT Supervisor',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Noe Cagomoc',
                'email' => 'npcagomoc@leoniogroup.com',
                'position' => 'Database Administrator',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Jev Galindez',
                'email' => 'jrgalindez@leoniogroup.com',
                'position' => 'Network Administrator',
            ],
            [
                'role_name' => 'IT Admin',
                'name' => 'Nia Sanchez',
                'email' => 'nmsanchez@leoniogroup.com',
                'position' => 'System Administrator',
            ],
            [
                'role_name' => 'Manager',
                'name' => 'Bon Caldito',
                'email' => 'bcaldito@leoniogroup.com',
                'position' => 'ICT Manager',
            ],
            // ICT =================================================

            // Leoniogroup =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Albert Gabion',
                'email' => 'algabion@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Almira Fabian',
                'email' => 'aefabian@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Aloha Camille Villapando',
                'email' => 'aavillapando@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Angel May Paor',
                'email' => 'aepaor@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Angelica Esguerra',
                'email' => 'acesguerra@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Aubrey Louise Perez',
                'email' => 'abperez@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Camille Mabaquiao',
                'email' => 'camabaquiao@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Cara A. Leonio',
                'email' => 'cbaleonio@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Cheyeane Arambulo',
                'email' => 'cmarambulo@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Chilet Cunanan',
                'email' => 'cccunanan@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Christiana Delos Santos',
                'email' => 'ccdelossantos@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Claire Leonio Guiyab',
                'email' => 'clguiyab@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Erica Joy Villagen',
                'email' => 'egvillagen@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Franklin Esma',
                'email' => 'fbesma@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Gretchen Molina',
                'email' => 'gbmolina@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jasmine Galutira',
                'email' => 'jrgalutira@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jean Tungala',
                'email' => 'jdtungala@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Joan Villalon',
                'email' => 'jdvillalon@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'John Crane',
                'email' => 'jwcrane@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jorly Hisarza',
                'email' => 'jihisarza@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kate De Jesus',
                'email' => 'kddejesus@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kristine Amatorio',
                'email' => 'kcamatorio@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leeann Paler',
                'email' => 'lmpaler@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leonio Group-HR',
                'email' => 'hr.services@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Liberato Lapina',
                'email' => 'lrlapina@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Maricel Guya',
                'email' => 'mrguya@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marinel Panizales',
                'email' => 'mlpanizales@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marivel Negrana',
                'email' => 'mbnegrana@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Mark Tyrone Baclig',
                'email' => 'mlbaclig@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marylen Yuson',
                'email' => 'mtyuson@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Michael Mark Villanueva',
                'email' => 'movillanueva@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ralph Conde',
                'email' => 'rdconde@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ramil Alcala',
                'email' => 'rsalcala@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Regina Magbitang',
                'email' => 'rfmagbitang@petrolift.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Reinalyn Evangelista',
                'email' => 'rkevangelista@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rocel Espino',
                'email' => 'rtespino@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Roma Cruz',
                'email' => 'rlcruz@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rosanne Bo',
                'email' => 'rfbo@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Roxane Tanael',
                'email' => 'rdtanael@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Samuel Sagun',
                'email' => 'scsagun@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Trisha Angela Cajalne',
                'email' => 'tdcajalne@leoniogroup.com',
                'position' => 'Staff',
            ],
            // Leoniogroup =================================================

            // Lami =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Aaron Red Almoniña',
                'email' => 'avalmonina@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Angelica Loterte',
                'email' => 'arloterte@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Anthony Bryan Escurel',
                'email' => 'aeescurel@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Arianne Destura',
                'email' => 'addestura@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ariel Laguardia',
                'email' => 'alluguardia@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ben Belwa',
                'email' => 'babelwa@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Charleane Cudal',
                'email' => 'crlandingin@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Clarence Jandoc',
                'email' => 'cbjandoc@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'COMREL Department',
                'email' => 'comrel01@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Data Management',
                'email' => 'mine.production@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Deanne Cucal',
                'email' => 'dlcucal@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Deo Sienes',
                'email' => 'dfseines@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Efelyn Fabro',
                'email' => 'esfabro@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Emil Carlos',
                'email' => 'edcarlos@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Emmanuel Altarejos',
                'email' => 'epaltarejos@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Erica Wayne Bal',
                'email' => 'egbal@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Eugene Jay Tacardon',
                'email' => 'ectacardon@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ginard Abuel',
                'email' => 'gmabuel@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Graciello Mae Cuasay',
                'email' => 'gpcuasay@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Imieren Comoda',
                'email' => 'iacomoda@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'James Mitchell Montalla',
                'email' => 'comrel02@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jay Delgra',
                'email' => 'jedelgra@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jay Mark Ebanculla',
                'email' => 'jmebanculla@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jennefer Hernandez',
                'email' => 'jmhernandez@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jenny Mae Del Pilar',
                'email' => 'jpdelpilar@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'John Go',
                'email' => 'jmgo@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Johny Ebron',
                'email' => 'jgebron@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kathleen Mortil',
                'email' => 'site.hr02@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Laboratory',
                'email' => 'laboratory@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Lawrence Leonio',
                'email' => 'lnleonio@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leah Peralta',
                'email' => 'site.accounting02@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Lester Polinar',
                'email' => 'llpolinar@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Lucy Marty',
                'email' => 'site.hr@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marvin Montalla',
                'email' => 'mbmontalla@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Mary Joy Lope',
                'email' => 'mslope@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Medardo Dangbis',
                'email' => 'surveyor@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Meliza Mayo',
                'email' => 'site.accounting01@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Michael Paul Munoz',
                'email' => 'mnmunoz@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Niño Cosme Navarro',
                'email' => 'comrel03@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Quen Russel Bautista',
                'email' => 'qsbautista@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Reynaldo Bautista Jr.',
                'email' => 'rbbautista@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Richard Coles',
                'email' => 'rlcoles@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Riolyn Mayo',
                'email' => 'rmmayo@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Roblezyra Manila',
                'email' => 'rgmanila@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Roilan Liwanag',
                'email' => 'rrliwanag@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Scalley Dela Cruz',
                'email' => 'ssdelacruz@lnl.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Warehouse',
                'email' => 'warehouse@lnl.com.ph',
                'position' => 'Staff',
            ],
            // Lami =================================================

            // LLRI =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Anthony Macalalag',
                'email' => 'admacalalag@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Brian Nicole Bal',
                'email' => 'bmbal@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jayson Pelarija',
                'email' => 'jppelarija@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jo Ann Ignacio',
                'email' => 'jmignacio@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Joanna Rubio',
                'email' => 'jprubio@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'John Jhay Duldulao',
                'email' => 'jmduldulao@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jonalyn Manaois',
                'email' => 'jcmanaois@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kevin Arnold Cuenca',
                'email' => 'kncuenca@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Layne Kristian Guzman',
                'email' => 'lgguzman@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leandro Luna',
                'email' => 'lrluna@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Limuel Macabunga',
                'email' => 'lnmacabunga@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Mary Jane Velez',
                'email' => 'mmvelez@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rafael Manaois',
                'email' => 'rfmanaois@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Renante Asido',
                'email' => 'rtasido@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Reynan Beng-Ad',
                'email' => 'rmbeng-ad@llri.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rochelle Misola',
                'email' => 'rmmisola@llri.com.ph',
                'position' => 'Staff',
            ],
            // LLRI =================================================

            // LHI =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Administrator LHI',
                'email' => 'administrator@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ana Denise Magbanua',
                'email' => 'armagbanua@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Angelica Viado',
                'email' => 'arviado@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'April Kristine Tiongco',
                'email' => 'amtiongco@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Areen Velasco',
                'email' => 'aovelasco@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Arnel Mamitag',
                'email' => 'almamitag@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Bernardo Sahagun',
                'email' => 'bdsahagun@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Bryan Galang',
                'email' => 'brgalang@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Carl Martin Hilario',
                'email' => 'cphilario@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Christian Co',
                'email' => 'cbco@landandlifestylepartners.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Clarissa Leonio Asuncion',
                'email' => 'ctlasuncion@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Cristel Laoyon',
                'email' => 'cnlaoyon@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'CTLA Office',
                'email' => 'ctlaoffice@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Czen Alfie Bico',
                'email' => 'cqbico@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Diomar Lazatin',
                'email' => 'dalazatin@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Emmanuel Malto',
                'email' => 'etmalto@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ephram Jose Valdez',
                'email' => 'edvaldez@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Eric Loreto',
                'email' => 'ebloreto@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Erna Ann Paraiso',
                'email' => 'ecparaiso@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Fedalyn Filio',
                'email' => 'fbfilio@landandlifestylepartners.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ferdinand Macabanti',
                'email' => 'fmmacabanti@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Geraldine Umlas',
                'email' => 'gpumlas@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Gerly Lising',
                'email' => 'golising@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Gregory Bituin',
                'email' => 'gpbituin@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Heizel Calilung',
                'email' => 'hgcalilung@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Helen Lazo',
                'email' => 'htlazo@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jan Ray Fedelino',
                'email' => 'jffedelino@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jannette Joyce Ferreria',
                'email' => 'jsferreria@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jay Mark Magpoc',
                'email' => 'jsmagpoc@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jeanny Macario',
                'email' => 'jrmacario@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jeffrey Sabado',
                'email' => 'jgsabado@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jericho Baluyut',
                'email' => 'jdbaluyut@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jerome Marasigan',
                'email' => 'jtmarasigan@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jonas Gatus',
                'email' => 'jbgatus@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Joy Martin',
                'email' => 'jlmartin@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Julia Cristobal',
                'email' => 'jacristobal@landandlifestylepartners.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Karina Bugante',
                'email' => 'kfbugante@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Katherine Manriza',
                'email' => 'krmanriza@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kayabe Ka',
                'email' => 'unladkomunidad@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kenneth Lombres',
                'email' => 'kclombres@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kris Marynette Jose',
                'email' => 'khjose@leoniogroup.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Kristine Joy Tayag',
                'email' => 'kptayag@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Larry Mercurio',
                'email' => 'lzmercurio@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leensey Poblete',
                'email' => 'lcpoblete@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leonardo Leonio',
                'email' => 'lll@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Leonio Land Sales',
                'email' => 'infosales@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'LLHI - Tiongco, April Kristine',
                'email' => 'technicalofficerjv@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'LLHI Official',
                'email' => 'llhi.official@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Mairece Vargas',
                'email' => 'mbvargas@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marcniel Alwin Agnes',
                'email' => 'muagnes@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Maria Cristina Jimenez',
                'email' => 'mcjimenez@landandlifestylepartners.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Michael Orpia',
                'email' => 'mlorpia@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Miguel Campanilla',
                'email' => 'mscampanilla@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Milanie Joy Baradi',
                'email' => 'msbaradi@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Nico Paul Huliganga',
                'email' => 'ndhuliganga@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Patricia Mas',
                'email' => 'psmas@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Pedrino Luben',
                'email' => 'pqluben@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rachel Matias',
                'email' => 'rcmatias@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Raeceleine Manuel',
                'email' => 'rmmanuel@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rafael Cortez',
                'email' => 'rdcortez@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ralph Laurence Chavenia',
                'email' => 'rtchavenia@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Randy Gaid',
                'email' => 'rlgaid@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Regina Magbitang',
                'email' => 'rfmagbitang@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Rosabella Lipsey',
                'email' => 'ralipsey@leonioland.com',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Val Lenry De La Torre',
                'email' => 'vtdelatorre@landandlifestylepartners.com',
                'position' => 'Staff',
            ],
            // LHI =================================================

            // TSMI =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Alvin Rimando',
                'email' => 'atrimando@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Alvin Vidal',
                'email' => 'aavidal@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Bbb Magtoto',
                'email' => 'bhmagtoto@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Daryl Dalapag',
                'email' => 'dcdalapag@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Dennis Esmalla',
                'email' => 'dmesmalla@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Edileo Barrios',
                'email' => 'edbarrios@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Francis Alcantara',
                'email' => 'foalcantara@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Isaias Menoras',
                'email' => 'ismenoras@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Jerry Angelo Lucanas',
                'email' => 'jglucanas@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'John Paulo Mesa',
                'email' => 'jcmesa@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'LPG Ma. Teresita',
                'email' => 'lpg.materesita@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Manuel Romero',
                'email' => 'mfromero@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Marlon De Guzman',
                'email' => 'mldeguzman@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Mary Catherine Zabal',
                'email' => 'mcbzabal@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Maryvonne Cruz',
                'email' => 'mjcruz@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Niel Ian Ferrancol',
                'email' => 'nfferrancol@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Anica',
                'email' => 'petro.anica@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Ausanee',
                'email' => 'petro.ausanee@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Cara',
                'email' => 'petro.cara@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Celine',
                'email' => 'petro.celine@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Claire',
                'email' => 'petro.claire@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Petro Helen',
                'email' => 'petro.helen@tsmi.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Siase Natividad Colapo',
                'email' => 'slcolapo@tsmi.com.ph',
                'position' => 'Staff',
            ],
            // TSMI =================================================

            // Petrolift =================================================
            [
                'role_name' => 'Employee',
                'name' => 'Carlo Leonio',
                'email' => 'cnleonio@petrolift.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Corazon Bejasa',
                'email' => 'cmbejasa@petrolift.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Ernesto Lutao Jr.',
                'email' => 'eglutao@petrolift.com.ph',
                'position' => 'Staff',
            ],
            [
                'role_name' => 'Employee',
                'name' => 'Melizza Vibar',
                'email' => 'mgvibar@petrolift.com.ph',
                'position' => 'Staff',
            ]
        ];

        foreach ($users as $userData) {   
            $role = DB::table('roles')
                ->where('role_name', $userData['role_name'])
                ->first();

            if (!$role) {
                $this->command->warn("Role '{$userData['role_name']}' not found. Skipping.");
                continue;
            }

            DB::table('users')->insert([
                'id' => Str::uuid(),
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'department_id' => $department->id,
                'position' => $userData['position'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}